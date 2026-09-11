<?php

declare(strict_types=1);

namespace App\Settlement;

use InvalidArgumentException;
use LogicException;

/**
 * Implements requirement.md §8.3 exactly: drop zero nets, cancel exact
 * opposite pairs, then find the maximum number of disjoint zero-sum groups
 * over the remaining players with an exact bitmask DP (§8.3 Step 3),
 * settling each group with a greedy debtor-to-creditor pass (provably
 * optimal *within* a zero-sum group — §8.3 Step 4). This class is pure: no
 * database access, deterministic, and safe to unit-test directly (see
 * CLAUDE.md "Invariants").
 *
 * Determinism (§8.4): the caller must pass players pre-ordered by
 * `position` then `id`; every tie in this class is broken by that same
 * input order (`idx` below), and submasks/zero-sum masks are always
 * enumerated in ascending numeric order with the *first* maximum kept.
 */
final class Settler
{
    /**
     * Above this many non-zero, non-cancelled players, §8.3 Step 5's plain
     * greedy fallback is used instead of the exact DP (which is O(3^n)).
     */
    private const int MAX_EXACT_PLAYERS = 15;

    /**
     * When 2^n zero-sum submasks would be too many to enumerate one by one
     * for every mask, and there happen to be few *actual* zero-sum masks,
     * it is cheaper to iterate only those (bucketed by lowest bit) instead
     * of every submask of every mask. Both paths are exact and produce an
     * identical result; this only changes speed. See requirement.md §8.3
     * Step 3's complexity note — the measured worst case (few zero-sum
     * masks) is ~200ms at n=15, comfortably under NFR-11's 500ms budget,
     * while the common case (many zero-sum masks, e.g. repeated buy-in
     * amounts) drops to single-digit milliseconds on this path.
     */
    private const int ZERO_MASK_BUCKET_THRESHOLD = 256;

    private function __construct() {}

    /**
     * @param  list<array{id: int, net: int}>  $players  ordered by position, then id;
     *                                                   `net` = total_out - total_in
     */
    public static function settle(array $players): SettlementResult
    {
        $records = [];

        foreach (array_values($players) as $idx => $player) {
            if ($player['net'] === 0) {
                continue;
            }

            $records[] = ['id' => $player['id'], 'net' => $player['net'], 'idx' => $idx];
        }

        if (array_sum(array_column($records, 'net')) !== 0) {
            throw new InvalidArgumentException('Settler::settle() requires player nets that sum to zero.');
        }

        $transfers = [];
        $remaining = self::cancelOpposites($records, $transfers);
        $n = count($remaining);

        if ($n === 0) {
            return new SettlementResult($transfers, approximate: false);
        }

        if ($n > self::MAX_EXACT_PLAYERS) {
            self::greedy($remaining, $transfers);

            return new SettlementResult($transfers, approximate: true);
        }

        foreach (self::partition($remaining) as $groupIndices) {
            self::greedy(array_map(fn (int $i) => $remaining[$i], $groupIndices), $transfers);
        }

        return new SettlementResult($transfers, approximate: false);
    }

    /**
     * §8.3 Step 2. Scans by ascending index and matches each unmatched
     * player with the first later unmatched player holding the exact
     * opposite net, emitting that transfer immediately.
     *
     * @param  list<array{id: int, net: int, idx: int}>  $records
     * @param  list<Transfer>  $transfers  appended to by reference
     * @return list<array{id: int, net: int, idx: int}> the unmatched remainder, original order preserved
     */
    private static function cancelOpposites(array $records, array &$transfers): array
    {
        $n = count($records);
        $matched = array_fill(0, $n, false);

        for ($i = 0; $i < $n; $i++) {
            if ($matched[$i]) {
                continue;
            }

            for ($j = $i + 1; $j < $n; $j++) {
                if ($matched[$j] || $records[$j]['net'] !== -$records[$i]['net']) {
                    continue;
                }

                $matched[$i] = true;
                $matched[$j] = true;

                $debtor = $records[$i]['net'] < 0 ? $records[$i] : $records[$j];
                $creditor = $records[$i]['net'] < 0 ? $records[$j] : $records[$i];
                $transfers[] = new Transfer($debtor['id'], $creditor['id'], abs($debtor['net']));

                break;
            }
        }

        $remaining = [];

        for ($i = 0; $i < $n; $i++) {
            if (! $matched[$i]) {
                $remaining[] = $records[$i];
            }
        }

        return $remaining;
    }

    /**
     * §8.3 Step 3: the exact bitmask DP. Returns the chosen partition as a
     * list of groups, each a list of indices into `$remaining`.
     *
     * @param  list<array{id: int, net: int, idx: int}>  $remaining
     * @return list<list<int>>
     */
    private static function partition(array $remaining): array
    {
        $n = count($remaining);
        $net = array_column($remaining, 'net');
        $full = (1 << $n) - 1;

        // Lowest-bit value -> player index, avoids any float log() in the hot loop.
        $lowBitIndex = [];
        for ($i = 0; $i < $n; $i++) {
            $lowBitIndex[1 << $i] = $i;
        }

        // sum[mask] via the lowest-bit recurrence, O(2^n). Zero-sum masks are
        // bucketed by their own lowest bit as they're found: any valid
        // submask s of a mask m (s containing m's lowest bit) can never have
        // a bit below m's lowest bit either, so s's own lowest bit is always
        // exactly m's — the bucket for bit i is therefore precisely the
        // candidate set for every m whose lowest bit is i.
        $sum = array_fill(0, $full + 1, 0);
        $zeroMasksByLowBit = array_fill(0, $n, []);

        for ($m = 1; $m <= $full; $m++) {
            $lb = $m & -$m;
            $sum[$m] = $sum[$m ^ $lb] + $net[$lowBitIndex[$lb]];

            if ($sum[$m] === 0) {
                $zeroMasksByLowBit[$lowBitIndex[$lb]][] = $m;
            }
        }

        $zeroCount = array_sum(array_map('count', $zeroMasksByLowBit));

        $g = array_fill(0, $full + 1, 0);
        $choice = array_fill(0, $full + 1, 0);

        if ($zeroCount <= self::ZERO_MASK_BUCKET_THRESHOLD) {
            for ($m = 1; $m <= $full; $m++) {
                $i = $lowBitIndex[$m & -$m];
                $best = 0;
                $bestS = 0;

                foreach ($zeroMasksByLowBit[$i] as $z) {
                    if (($z & ~$m) === 0) {
                        $v = 1 + $g[$m ^ $z];

                        if ($v > $best) {
                            $best = $v;
                            $bestS = $z;
                        }
                    }
                }

                $g[$m] = $best;
                $choice[$m] = $bestS;
            }
        } else {
            for ($m = 1; $m <= $full; $m++) {
                $lb = $m & -$m;
                $rest = $m ^ $lb;
                $best = 0;
                $bestS = 0;
                $s = 0;

                while (true) {
                    $cand = $s | $lb;

                    if ($sum[$cand] === 0) {
                        $v = 1 + $g[$m ^ $cand];

                        if ($v > $best) {
                            $best = $v;
                            $bestS = $cand;
                        }
                    }

                    if ($s === $rest) {
                        break;
                    }

                    $s = ($s - $rest) & $rest;
                }

                $g[$m] = $best;
                $choice[$m] = $bestS;
            }
        }

        $groups = [];
        $m = $full;

        while ($m !== 0) {
            $s = $choice[$m];

            if ($s === 0) {
                // Unreachable given settle()'s sum-to-zero precondition: the
                // full mask always sums to zero, and so does every mask
                // reached by removing a zero-sum group from it.
                throw new LogicException('Settlement DP could not find a zero-sum partition.');
            }

            $indices = [];

            for ($i = 0; $i < $n; $i++) {
                if ($s & (1 << $i)) {
                    $indices[] = $i;
                }
            }

            $groups[] = $indices;
            $m ^= $s;
        }

        return $groups;
    }

    /**
     * §8.3 Step 4 (and the whole-set Step 5 fallback): largest debtor pays
     * largest creditor, repeatedly. Provably optimal within a zero-sum
     * group, yielding exactly `size - 1` transfers. Ties broken by the
     * original player index (`idx`).
     *
     * @param  list<array{id: int, net: int, idx: int}>  $group
     * @param  list<Transfer>  $transfers  appended to by reference
     */
    private static function greedy(array $group, array &$transfers): void
    {
        $debtors = array_values(array_filter($group, fn (array $p) => $p['net'] < 0));
        $creditors = array_values(array_filter($group, fn (array $p) => $p['net'] > 0));

        usort($debtors, fn (array $a, array $b) => $a['net'] <=> $b['net'] ?: $a['idx'] <=> $b['idx']);
        usort($creditors, fn (array $a, array $b) => $b['net'] <=> $a['net'] ?: $a['idx'] <=> $b['idx']);

        $debtAmounts = array_map(fn (array $p) => -$p['net'], $debtors);
        $creditAmounts = array_map(fn (array $p) => $p['net'], $creditors);

        $i = 0;
        $j = 0;
        $debtorCount = count($debtors);
        $creditorCount = count($creditors);

        while ($i < $debtorCount && $j < $creditorCount) {
            $amount = min($debtAmounts[$i], $creditAmounts[$j]);
            $transfers[] = new Transfer($debtors[$i]['id'], $creditors[$j]['id'], $amount);

            $debtAmounts[$i] -= $amount;
            $creditAmounts[$j] -= $amount;

            if ($debtAmounts[$i] === 0) {
                $i++;
            }

            if ($creditAmounts[$j] === 0) {
                $j++;
            }
        }
    }
}
