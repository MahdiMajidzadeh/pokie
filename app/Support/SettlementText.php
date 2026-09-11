<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Table;

/**
 * The settled table as plain text, ready to paste into a group chat (the
 * natural next step after FR-32's "share the settled table"). Deliberately
 * messaging-app friendly: no markdown, no column alignment (proportional
 * fonts would wreck it), an ASCII hyphen for minus rather than U+2212, and
 * the read-only share link on the last line so recipients can open the
 * live page.
 *
 * Mirrors the Board view's own ordering — results winners-first, payments
 * grouped by payer — so the text and the screen never disagree.
 */
final class SettlementText
{
    private function __construct() {}

    public static function for(Table $table): string
    {
        $ledger = Ledger::for($table);

        $lines = [$table->name ?: 'Poker night', '', 'Results'];

        $players = $table->players()->get()
            ->sortByDesc(fn ($player) => $ledger->forPlayer($player->id)['net'])
            ->values();

        foreach ($players as $player) {
            $lines[] = $player->name.' '.self::signed($ledger->forPlayer($player->id)['net']);
        }

        $payments = $table->payments()
            ->with(['fromPlayer', 'toPlayer'])
            ->orderBy('from_player_id')
            ->orderBy('id')
            ->get();

        $lines[] = '';
        $lines[] = 'Payments';

        if ($payments->isEmpty()) {
            $lines[] = 'No payments needed - everyone broke even.';
        } else {
            foreach ($payments as $payment) {
                $lines[] = sprintf(
                    '%s pays %s %s%s',
                    $payment->fromPlayer->name,
                    $payment->toPlayer->name,
                    number_format($payment->amount),
                    $payment->paid_at ? ' (paid)' : '',
                );
            }

            $lines[] = '';
            $lines[] = sprintf('%d of %d paid', $payments->whereNotNull('paid_at')->count(), $payments->count());
        }

        $lines[] = '';
        $lines[] = url('/'.$table->table_hash);

        return implode("\n", $lines);
    }

    private static function signed(int $net): string
    {
        if ($net === 0) {
            return '0';
        }

        return ($net > 0 ? '+' : '-').number_format(abs($net));
    }
}
