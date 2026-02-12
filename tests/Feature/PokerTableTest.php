<?php

declare(strict_types=1);

use App\Models\BuyIn;
use App\Models\Payback;
use App\Models\Settlement;
use App\Models\Table;

it('home page renders', function () {
    $response = $this->get('/');
    $response->assertStatus(200);
    $response->assertSee('Create a table');
});

it('create table redirects to manager url', function () {
    $response = $this->post('/tables', ['name' => 'Friday game']);
    $response->assertRedirect();
    $this->assertDatabaseHas('poker_tables', ['name' => 'Friday game']);

    $table = Table::where('name', 'Friday game')->first();
    expect($table->token)->not->toBeNull();
    expect($table->manager_token)->not->toBeNull();
    $redirect = $response->headers->get('Location');
    expect($redirect)->toContain($table->token);
    expect($redirect)->toContain($table->manager_token);
});

it('view only page has no forms', function () {
    Table::create([
        'name' => 'Test',
        'token' => 'viewtoken123',
        'manager_token' => 'mgrtoken456',
    ]);

    $response = $this->get('/t/viewtoken123');
    $response->assertStatus(200);
    $response->assertSee('Test');
    $response->assertSee('View only');
    $response->assertDontSee('Add player');
});

it('manager page has forms', function () {
    Table::create([
        'name' => 'Test',
        'token' => 'viewtoken123',
        'manager_token' => 'mgrtoken456',
    ]);

    $response = $this->get('/t/viewtoken123/mgrtoken456');
    $response->assertStatus(200);
    $response->assertSee('Add player');
    $response->assertSee('Record buy-in');
    $response->assertSee('Record payback');
});

it('invalid manager token redirects to view', function () {
    Table::create([
        'name' => 'Test',
        'token' => 'viewtoken123',
        'manager_token' => 'mgrtoken456',
    ]);

    $response = $this->get('/t/viewtoken123/wrongmanager');
    $response->assertRedirect('/t/viewtoken123');
    $response->assertSessionHas('error');
});

it('add player requires manager token', function () {
    Table::create([
        'name' => 'Test',
        'token' => 'viewtoken123',
        'manager_token' => 'mgrtoken456',
    ]);

    $response = $this->post('/t/viewtoken123/wrongmanager/players', ['name' => 'Alice']);
    $response->assertRedirect('/t/viewtoken123');
    $this->assertDatabaseMissing('players', ['name' => 'Alice']);
});

it('full flow buyin and payback', function () {
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
    expect((float) $table->players->first()->amount)->toBe(150.0);
    expect((float) $table->bank)->toBe(150.0);

    $this->post('/t/t1/m1/paybacks', ['player_id' => $player->id, 'amount' => 40]);

    $table->refresh();
    $table->load(['players.buyIns', 'players.paybacks']);
    expect((float) $table->players->first()->amount)->toBe(110.0);
    expect((float) $table->bank)->toBe(110.0);
});

it('minimum settlement transactions', function () {
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

    expect($txs)->toHaveCount(3);
});

it('withdraw direction debtor pays creditor', function () {
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
    expect($txs->contains(fn ($t) => $t->from->name === 'Ali F.' && $t->to->name === 'MohammadReza' && abs($t->amount - 2) < 0.01))->toBeTrue();
});

it('destroy buy-in requires manager token', function () {
    $table = Table::create(['name' => 'T', 'token' => 't1', 'manager_token' => 'm1']);
    $player = $table->players()->create(['name' => 'Alice']);
    $buyIn = BuyIn::create(['table_id' => $table->id, 'player_id' => $player->id, 'amount' => -100]);

    $response = $this->delete("/t/t1/wrongmanager/buy-ins/{$buyIn->id}");
    $response->assertRedirect('/t/t1');
    $this->assertDatabaseHas('buy_ins', ['id' => $buyIn->id]);
});

it('destroy buy-in deletes record with valid manager token', function () {
    $table = Table::create(['name' => 'T', 'token' => 't1', 'manager_token' => 'm1']);
    $player = $table->players()->create(['name' => 'Alice']);
    $buyIn = BuyIn::create(['table_id' => $table->id, 'player_id' => $player->id, 'amount' => -100]);

    $response = $this->delete("/t/t1/m1/buy-ins/{$buyIn->id}");
    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseMissing('buy_ins', ['id' => $buyIn->id]);
});

it('destroy payback deletes record with valid manager token', function () {
    $table = Table::create(['name' => 'T', 'token' => 't1', 'manager_token' => 'm1']);
    $player = $table->players()->create(['name' => 'Alice']);
    $payback = Payback::create(['table_id' => $table->id, 'player_id' => $player->id, 'amount' => 50]);

    $response = $this->delete("/t/t1/m1/paybacks/{$payback->id}");
    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseMissing('paybacks', ['id' => $payback->id]);
});

it('destroy settlement deletes record with valid manager token', function () {
    $table = Table::create(['name' => 'T', 'token' => 't1', 'manager_token' => 'm1']);
    $player = $table->players()->create(['name' => 'Alice']);
    $settlement = Settlement::create(['table_id' => $table->id, 'player_id' => $player->id, 'amount' => 10]);

    $response = $this->delete("/t/t1/m1/settlements/{$settlement->id}");
    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseMissing('settlements', ['id' => $settlement->id]);
});

it('store buy-in with amount zero returns validation error', function () {
    $table = Table::create(['name' => 'T', 'token' => 't1', 'manager_token' => 'm1']);
    $player = $table->players()->create(['name' => 'Alice']);

    $response = $this->post('/t/t1/m1/buy-ins', ['player_id' => $player->id, 'amount' => 0]);
    $response->assertSessionHasErrors('amount');
});

it('store settlement with invalid player_id returns validation error', function () {
    $table = Table::create(['name' => 'T', 'token' => 't1', 'manager_token' => 'm1']);
    $otherTable = Table::create(['name' => 'Other', 'token' => 't2', 'manager_token' => 'm2']);
    $otherPlayer = $otherTable->players()->create(['name' => 'Other Player']);

    $response = $this->post('/t/t1/m1/settlements', ['player_id' => $otherPlayer->id, 'amount' => 10]);
    $response->assertSessionHasErrors('player_id');
});

it('store player with empty name returns validation error', function () {
    $table = Table::create(['name' => 'T', 'token' => 't1', 'manager_token' => 'm1']);

    $response = $this->post('/t/t1/m1/players', ['name' => '']);
    $response->assertSessionHasErrors('name');
});
