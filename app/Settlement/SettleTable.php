<?php

declare(strict_types=1);

namespace App\Settlement;

use App\Enums\TableStatus;
use App\Models\Payment;
use App\Models\Table;
use App\Support\Ledger;
use Illuminate\Support\Facades\DB;

/**
 * FR-24..FR-27: validates a table can be settled, runs the solver, and
 * persists the resulting payment list inside one transaction.
 */
final class SettleTable
{
    /**
     * @throws SettlementBlocked
     */
    public function __invoke(Table $table): Table
    {
        return DB::transaction(function () use ($table) {
            if (! $table->isOpen()) {
                throw SettlementBlocked::alreadySettled();
            }

            $ledger = Ledger::for($table);

            if (! $ledger->isBalanced()) {
                throw SettlementBlocked::unbalanced($ledger->imbalance);
            }

            if ($ledger->playersWithEntries < 2) {
                throw SettlementBlocked::notEnoughPlayers();
            }

            $players = $table->players()->get(['id'])
                ->map(fn ($player) => ['id' => $player->id, 'net' => $ledger->forPlayer($player->id)['net']])
                ->all();

            $result = Settler::settle($players);

            // Grouped by payer for display (requirement.md §8.5 "Sorting for
            // display"): stable sort keeps the deterministic solver order
            // within each payer's group.
            $transfers = collect($result->transfers)->sortBy('from', descending: false)->values();

            foreach ($transfers as $transfer) {
                Payment::create([
                    'table_id' => $table->id,
                    'from_player_id' => $transfer->from,
                    'to_player_id' => $transfer->to,
                    'amount' => $transfer->amount,
                ]);
            }

            $table->update([
                'status' => TableStatus::Settled,
                'settled_at' => now(),
            ]);

            return $table;
        });
    }
}
