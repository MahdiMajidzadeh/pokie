<?php

namespace Tests\Feature;

use App\Models\BuyIn;
use App\Models\Payback;
use App\Models\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PokerTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Create a poker table');
    }

    public function test_create_table_redirects_to_manager_url(): void
    {
        $response = $this->post('/tables', ['name' => 'Friday game']);
        $response->assertRedirect();
        $this->assertDatabaseHas('poker_tables', ['name' => 'Friday game']);

        $table = Table::where('name', 'Friday game')->first();
        $this->assertNotNull($table->token);
        $this->assertNotNull($table->manager_token);
        $redirect = $response->headers->get('Location');
        $this->assertStringContainsString($table->token, $redirect);
        $this->assertStringContainsString($table->manager_token, $redirect);
    }

    public function test_view_only_page_has_no_forms(): void
    {
        $table = Table::create([
            'name' => 'Test',
            'token' => 'viewtoken123',
            'manager_token' => 'mgrtoken456',
        ]);

        $response = $this->get('/t/viewtoken123');
        $response->assertStatus(200);
        $response->assertSee('Test');
        $response->assertSee('View only');
        $response->assertDontSee('Add player');
    }

    public function test_manager_page_has_forms(): void
    {
        $table = Table::create([
            'name' => 'Test',
            'token' => 'viewtoken123',
            'manager_token' => 'mgrtoken456',
        ]);

        $response = $this->get('/t/viewtoken123/mgrtoken456');
        $response->assertStatus(200);
        $response->assertSee('Add player');
        $response->assertSee('Record buy-in');
        $response->assertSee('Record payback');
    }

    public function test_invalid_manager_token_redirects_to_view(): void
    {
        $table = Table::create([
            'name' => 'Test',
            'token' => 'viewtoken123',
            'manager_token' => 'mgrtoken456',
        ]);

        $response = $this->get('/t/viewtoken123/wrongmanager');
        $response->assertRedirect('/t/viewtoken123');
        $response->assertSessionHas('error');
    }

    public function test_add_player_requires_manager_token(): void
    {
        $table = Table::create([
            'name' => 'Test',
            'token' => 'viewtoken123',
            'manager_token' => 'mgrtoken456',
        ]);

        $response = $this->post('/t/viewtoken123/wrongmanager/players', ['name' => 'Alice']);
        $response->assertRedirect('/t/viewtoken123');
        $this->assertDatabaseMissing('players', ['name' => 'Alice']);
    }

    public function test_full_flow_buyin_and_payback(): void
    {
        $table = Table::create([
            'name' => 'Game',
            'token' => 't1',
            'manager_token' => 'm1',
        ]);

        $this->post('/t/t1/m1/players', ['name' => 'Alice']);
        $this->assertDatabaseHas('players', ['name' => 'Alice']);

        $player = $table->players()->first();

        $this->post('/t/t1/m1/buy-ins', ['player_id' => $player->id, 'amount' => 100]);
        $this->post('/t/t1/m1/buy-ins', ['player_id' => $player->id, 'amount' => 50]);

        $table->refresh();
        $table->load(['players.buyIns', 'players.paybacks']);
        $this->assertSame(150.0, (float) $table->players->first()->amount);
        $this->assertSame(150.0, (float) $table->bank);

        $this->post('/t/t1/m1/paybacks', ['player_id' => $player->id, 'amount' => 40]);

        $table->refresh();
        $table->load(['players.buyIns', 'players.paybacks']);
        $this->assertSame(110.0, (float) $table->players->first()->amount);
        $this->assertSame(110.0, (float) $table->bank);
    }

    public function test_minimum_settlement_transactions(): void
    {
        $table = Table::create([
            'name' => 'Min Trans',
            'token' => 't2',
            'manager_token' => 'm2',
        ]);

        $players = collect(['A', 'B', 'C', 'D', 'E'])->map(fn ($name) => $table->players()->create(['name' => $name]))->all();

        // display_amount: A=-3, B=-2, C=-2, D=+3, E=+4 (counterexample: greedy gives 4, optimal gives 3)
        BuyIn::create(['table_id' => $table->id, 'player_id' => $players[0]->id, 'amount' => -10]);
        Payback::create(['table_id' => $table->id, 'player_id' => $players[0]->id, 'amount' => 7]);

        BuyIn::create(['table_id' => $table->id, 'player_id' => $players[1]->id, 'amount' => -10]);
        Payback::create(['table_id' => $table->id, 'player_id' => $players[1]->id, 'amount' => 8]);

        BuyIn::create(['table_id' => $table->id, 'player_id' => $players[2]->id, 'amount' => -10]);
        Payback::create(['table_id' => $table->id, 'player_id' => $players[2]->id, 'amount' => 8]);

        BuyIn::create(['table_id' => $table->id, 'player_id' => $players[3]->id, 'amount' => -5]);
        Payback::create(['table_id' => $table->id, 'player_id' => $players[3]->id, 'amount' => 8]);

        BuyIn::create(['table_id' => $table->id, 'player_id' => $players[4]->id, 'amount' => -10]);
        Payback::create(['table_id' => $table->id, 'player_id' => $players[4]->id, 'amount' => 14]);

        $table->load(['players.buyIns', 'players.paybacks', 'players.settlements']);

        $txs = $table->getMinimumSettlementTransactions();

        $this->assertCount(3, $txs, 'Optimal solution should use 3 transactions, not 4 (greedy)');
    }

    public function test_withdraw_direction_debtor_pays_creditor(): void
    {
        $table = Table::create([
            'name' => 'Dir Test',
            'token' => 't3',
            'manager_token' => 'm3',
        ]);

        $majid = $table->players()->create(['name' => 'Majid']);
        $ahmad = $table->players()->create(['name' => 'Ahmad']);
        $hamed = $table->players()->create(['name' => 'Hamed']);
        $sogand = $table->players()->create(['name' => 'Sogand']);
        $aliSh = $table->players()->create(['name' => 'Ali Sh.']);
        $mr = $table->players()->create(['name' => 'MohammadReza']);
        $aliF = $table->players()->create(['name' => 'Ali F.']);

        foreach ([[$majid, -8], [$ahmad, -9], [$hamed, -10], [$sogand, 14], [$aliSh, 27], [$mr, 2], [$aliF, -16]] as [$p, $target]) {
            if ($target < 0) {
                BuyIn::create(['table_id' => $table->id, 'player_id' => $p->id, 'amount' => -abs($target) * 2]);
                Payback::create(['table_id' => $table->id, 'player_id' => $p->id, 'amount' => abs($target)]);
            } else {
                BuyIn::create(['table_id' => $table->id, 'player_id' => $p->id, 'amount' => -1]);
                Payback::create(['table_id' => $table->id, 'player_id' => $p->id, 'amount' => 1 + $target]);
            }
        }

        $table->load(['players.buyIns', 'players.paybacks', 'players.settlements']);

        $txs = $table->getMinimumSettlementTransactions();

        $debtorIds = collect([$majid, $ahmad, $hamed, $aliF])->pluck('id');
        $creditorIds = collect([$sogand, $aliSh, $mr])->pluck('id');

        foreach ($txs as $tx) {
            $this->assertTrue($debtorIds->contains($tx->from->id), "From must be debtor: {$tx->from->name}");
            $this->assertTrue($creditorIds->contains($tx->to->id), "To must be creditor: {$tx->to->name}");
        }
        $this->assertTrue($txs->contains(fn ($t) => $t->from->name === 'Ali F.' && $t->to->name === 'MohammadReza' && abs($t->amount - 2) < 0.01));
    }
}
