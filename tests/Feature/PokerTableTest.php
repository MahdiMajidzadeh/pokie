<?php

namespace Tests\Feature;

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
}
