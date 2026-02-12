<?php

declare(strict_types=1);

use App\Models\Table;

it('validates player_id belongs to table', function () {
    $table = Table::create(['name' => 'T', 'token' => 't1', 'manager_token' => 'm1']);
    $otherTable = Table::create(['name' => 'Other', 'token' => 't2', 'manager_token' => 'm2']);
    $otherPlayer = $otherTable->players()->create(['name' => 'Other']);

    $response = $this->post('/t/t1/m1/buy-ins', ['player_id' => $otherPlayer->id, 'amount' => 100]);

    $response->assertSessionHasErrors('player_id');
});

it('validates amount min 0.01', function () {
    $table = Table::create(['name' => 'T', 'token' => 't1', 'manager_token' => 'm1']);
    $player = $table->players()->create(['name' => 'Alice']);

    $response = $this->post('/t/t1/m1/buy-ins', ['player_id' => $player->id, 'amount' => 0]);

    $response->assertSessionHasErrors('amount');
});

it('accepts valid buy-in', function () {
    $table = Table::create(['name' => 'T', 'token' => 't1', 'manager_token' => 'm1']);
    $player = $table->players()->create(['name' => 'Alice']);

    $response = $this->post('/t/t1/m1/buy-ins', ['player_id' => $player->id, 'amount' => 100]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('buy_ins', ['player_id' => $player->id]);
});
