<?php

declare(strict_types=1);

use App\Models\BuyIn;
use App\Models\Payback;
use App\Models\Player;
use App\Models\Table;

it('table_balance returns sum of buy-ins', function () {
    $table = Table::create(['name' => 'T', 'token' => 't1', 'manager_token' => 'm1']);
    $player = $table->players()->create(['name' => 'Alice']);

    BuyIn::create(['table_id' => $table->id, 'player_id' => $player->id, 'amount' => -100]);
    BuyIn::create(['table_id' => $table->id, 'player_id' => $player->id, 'amount' => -50]);

    expect((float) $table->table_balance)->toBe(-150.0);
});

it('bank attribute returns correct formula', function () {
    $table = Table::create(['name' => 'T', 'token' => 't1', 'manager_token' => 'm1']);
    $player = $table->players()->create(['name' => 'Alice']);

    BuyIn::create(['table_id' => $table->id, 'player_id' => $player->id, 'amount' => -100]);
    Payback::create(['table_id' => $table->id, 'player_id' => $player->id, 'amount' => 40]);

    $table->refresh();
    $table->load(['buyIns', 'paybacks']);

    expect((float) $table->bank)->toBe(60.0);
});

it('getMinimumSettlementTransactions returns empty for no players', function () {
    $table = Table::create(['name' => 'T', 'token' => 't1', 'manager_token' => 'm1']);

    $txs = $table->getMinimumSettlementTransactions();

    expect($txs)->toHaveCount(0);
});

it('getMinimumSettlementTransactions returns empty for balanced players', function () {
    $table = Table::create(['name' => 'T', 'token' => 't1', 'manager_token' => 'm1']);
    $player = $table->players()->create(['name' => 'Alice']);
    BuyIn::create(['table_id' => $table->id, 'player_id' => $player->id, 'amount' => -100]);
    Payback::create(['table_id' => $table->id, 'player_id' => $player->id, 'amount' => 100]);

    $table->load(['players.buyIns', 'players.paybacks', 'players.settlements']);

    $txs = $table->getMinimumSettlementTransactions();

    expect($txs)->toHaveCount(0);
});

it('getMinimumSettlementTransactions returns single transaction for one debtor and one creditor', function () {
    $table = Table::create(['name' => 'T', 'token' => 't1', 'manager_token' => 'm1']);
    $debtor = $table->players()->create(['name' => 'Debtor']);
    $creditor = $table->players()->create(['name' => 'Creditor']);

    BuyIn::create(['table_id' => $table->id, 'player_id' => $debtor->id, 'amount' => -100]);
    Payback::create(['table_id' => $table->id, 'player_id' => $debtor->id, 'amount' => 40]);

    BuyIn::create(['table_id' => $table->id, 'player_id' => $creditor->id, 'amount' => -10]);
    Payback::create(['table_id' => $table->id, 'player_id' => $creditor->id, 'amount' => 70]);

    $table->load(['players.buyIns', 'players.paybacks', 'players.settlements']);

    $txs = $table->getMinimumSettlementTransactions();

    expect($txs)->toHaveCount(1);
    expect($txs->first()->from->name)->toBe('Debtor');
    expect($txs->first()->to->name)->toBe('Creditor');
    expect($txs->first()->amount)->toBe(60.0);
});
