<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\Table;

/**
 * The derived money view of a table (requirement.md §5.3), always computed
 * fresh from `entries` — never from a cached column (NFR-4).
 */
final class Ledger
{
    /**
     * @param  array<int, array{total_in: int, total_out: int, net: int}>  $byPlayer  keyed by player_id
     */
    private function __construct(
        public readonly array $byPlayer,
        public readonly int $potIn,
        public readonly int $potOut,
        public readonly int $onTable,
        public readonly int $imbalance,
        public readonly int $playersWithEntries,
    ) {}

    public static function for(Table $table): self
    {
        // Raw rows still go through Entry's casts when hydrated, so `type`
        // arrives as an EntryType enum instance (not the plain DB string) —
        // compare against the enum case, not its ->value.
        $rows = Entry::query()
            ->where('table_id', $table->id)
            ->selectRaw('player_id, type, SUM(amount) as total')
            ->groupBy('player_id', 'type')
            ->get();

        $byPlayer = [];

        foreach ($rows as $row) {
            $playerId = (int) $row->player_id;
            $byPlayer[$playerId] ??= ['total_in' => 0, 'total_out' => 0, 'net' => 0];

            if ($row->type === EntryType::BuyIn) {
                $byPlayer[$playerId]['total_in'] = (int) $row->total;
            } else {
                $byPlayer[$playerId]['total_out'] = (int) $row->total;
            }
        }

        foreach ($byPlayer as $playerId => $totals) {
            $byPlayer[$playerId]['net'] = $totals['total_out'] - $totals['total_in'];
        }

        $potIn = array_sum(array_column($byPlayer, 'total_in'));
        $potOut = array_sum(array_column($byPlayer, 'total_out'));

        return new self(
            byPlayer: $byPlayer,
            potIn: $potIn,
            potOut: $potOut,
            onTable: $potIn - $potOut,
            imbalance: array_sum(array_column($byPlayer, 'net')),
            playersWithEntries: count($byPlayer),
        );
    }

    /** @return array{total_in: int, total_out: int, net: int} */
    public function forPlayer(int $playerId): array
    {
        return $this->byPlayer[$playerId] ?? ['total_in' => 0, 'total_out' => 0, 'net' => 0];
    }

    public function isBalanced(): bool
    {
        return $this->imbalance === 0;
    }
}
