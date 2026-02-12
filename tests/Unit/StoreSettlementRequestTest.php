<?php

declare(strict_types=1);

use App\Models\Table;

it('validates player_id belongs to table', function () {
    $table = Table::create(['name' => 'T', 'token' => 't1', 'manager_token' => 'm1']);
    $otherTable = Table::create(['name' => 'Other', 'token' => 't2', 'manager_token' => 'm2']);
    $otherPlayer = $otherTable->players()->create(['name' => 'Other']);

    $response = $this->post('/t/t1/m1/settlements', ['player_id' => $otherPlayer->id, 'amount' => 10]);

    $response->assertSessionHasErrors('player_id');
});

it('validates amount is numeric', function () {
    $table = Table::create(['name' => 'T', 'token' => 't1', 'manager_token' => 'm1']);
    $player = $table->players()->create(['name' => 'Alice']);

    $response = $this->post('/t/t1/m1/settlements', ['player_id' => $player->id, 'amount' => 'invalid']);

    $response->assertSessionHasErrors('amount');
});

it('accepts valid settlement', function () {
    $table = Table::create(['name' => 'T', 'token' => 't1', 'manager_token' => 'm1']);
    $player = $table->players()->create(['name' => 'Alice']);

    $response = $this->post('/t/t1/m1/settlements', ['player_id' => $player->id, 'amount' => 10]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('settlements', ['player_id' => $player->id]);
});
