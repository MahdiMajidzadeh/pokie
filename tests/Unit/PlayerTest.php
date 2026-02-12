<?php

declare(strict_types=1);

use App\Models\BuyIn;
use App\Models\Payback;
use App\Models\Player;
use App\Models\Settlement;
use App\Models\Table;

it('amount attribute returns correct balance from buy-ins and paybacks', function () {
    $table = Table::create(['name' => 'T', 'token' => 't1', 'manager_token' => 'm1']);
    $player = $table->players()->create(['name' => 'Alice']);

    BuyIn::create(['table_id' => $table->id, 'player_id' => $player->id, 'amount' => -100]);
    BuyIn::create(['table_id' => $table->id, 'player_id' => $player->id, 'amount' => -50]);
    Payback::create(['table_id' => $table->id, 'player_id' => $player->id, 'amount' => 40]);

    $player->refresh();

    expect((float) $player->amount)->toBe(110.0);
});

it('display_amount includes settlements', function () {
    $table = Table::create(['name' => 'T', 'token' => 't1', 'manager_token' => 'm1']);
    $player = $table->players()->create(['name' => 'Alice']);

    BuyIn::create(['table_id' => $table->id, 'player_id' => $player->id, 'amount' => -100]);
    Payback::create(['table_id' => $table->id, 'player_id' => $player->id, 'amount' => 50]);
    Settlement::create(['table_id' => $table->id, 'player_id' => $player->id, 'amount' => 30]);

    $player->refresh();
    $player->load(['buyIns', 'paybacks', 'settlements']);

    expect((float) $player->display_amount)->toBe(-20.0);
});

it('records attribute returns sorted by created_at', function () {
    $table = Table::create(['name' => 'T', 'token' => 't1', 'manager_token' => 'm1']);
    $player = $table->players()->create(['name' => 'Alice']);

    $buyIn = BuyIn::create(['table_id' => $table->id, 'player_id' => $player->id, 'amount' => -100]);
    $payback = Payback::create(['table_id' => $table->id, 'player_id' => $player->id, 'amount' => 50]);
    $settlement = Settlement::create(['table_id' => $table->id, 'player_id' => $player->id, 'amount' => 10]);

    $player->refresh();

    $records = $player->records;

    expect($records)->toHaveCount(3);
    expect($records->first()->type)->toBe('buy_in');
    expect($records->last()->type)->toBe('settlement');
});
