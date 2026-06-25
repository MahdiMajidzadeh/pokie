<?php

declare(strict_types=1);

use App\Models\BuyIn;
use App\Models\Payback;
use App\Models\Table;

it('dumps settlement directions', function () {
    $t = Table::create(['name' => 'dbg', 'token' => 'dbgx', 'manager_token' => 'mdbg']);
    $names = ['Majid' => -8, 'Ahmad' => -9, 'Hamed' => -10, 'Sogand' => 14, 'Ali Sh.' => 27, 'MohammadReza' => 2, 'Ali F.' => -16];
    $players = [];
    foreach ($names as $n => $target) {
        $p = $t->players()->create(['name' => $n]);
        $players[$n] = $p;
        if ($target < 0) {
            BuyIn::create(['table_id' => $t->id, 'player_id' => $p->id, 'amount' => -abs($target) * 2]);
            Payback::create(['table_id' => $t->id, 'player_id' => $p->id, 'amount' => abs($target)]);
        } else {
            BuyIn::create(['table_id' => $t->id, 'player_id' => $p->id, 'amount' => -1]);
            Payback::create(['table_id' => $t->id, 'player_id' => $p->id, 'amount' => 1 + $target]);
        }
    }
    $t->load(['players.buyIns', 'players.paybacks', 'players.settlements']);
    $bal = [];
    foreach ($t->players as $p) {
        $bal[$p->name] = round($p->display_amount, 1);
    }
    $lines = [];
    foreach ($t->getMinimumSettlementTransactions() as $tx) {
        $flag = $bal[$tx->to->name] < 0 ? '  <-- recipient is a DEBTOR (WRONG)' : '';
        $lines[] = sprintf('%s -> %s  amount=%.2f  (recipient bal %+.0f)%s', $tx->from->name, $tx->to->name, $tx->amount, $bal[$tx->to->name], $flag);
    }
    expect($lines)->toBe(['__DUMP__'.implode(' || ', $lines)]);
});
