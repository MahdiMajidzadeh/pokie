<?php

declare(strict_types=1);

use App\Settlement\SettlementResult;
use App\Settlement\Settler;

/**
 * Independent brute-force reference: the true maximum number of disjoint
 * zero-sum groups a set of nets can be partitioned into, found by trying
 * every subset containing the first remaining element (so each partition
 * is counted once) and recursing on the rest. O(2^n) per level — fine for
 * the small n this is used with. Deliberately NOT bitmask-DP-with-
 * lowest-bit-trick, so it can't share a bug with Settler's own algorithm.
 *
 * @param  list<int>  $nets
 */
function bruteForceMaxGroups(array $nets): int
{
    $n = count($nets);

    if ($n === 0) {
        return 0;
    }

    $best = 0;

    for ($mask = 1; $mask < (1 << $n); $mask++) {
        if (! ($mask & 1)) {
            continue; // every subset considered must contain index 0
        }

        $sum = 0;
        $rest = [];

        for ($i = 0; $i < $n; $i++) {
            if ($mask & (1 << $i)) {
                $sum += $nets[$i];
            } else {
                $rest[] = $nets[$i];
            }
        }

        if ($sum === 0) {
            $best = max($best, 1 + bruteForceMaxGroups($rest));
        }
    }

    return $best;
}

/** @param list<array{id: int, net: int}> $players */
function greedyTransferCount(array $players): int
{
    $debtors = [];
    $creditors = [];

    foreach ($players as $p) {
        if ($p['net'] < 0) {
            $debtors[] = -$p['net'];
        } elseif ($p['net'] > 0) {
            $creditors[] = $p['net'];
        }
    }

    rsort($debtors);
    rsort($creditors);

    $i = 0;
    $j = 0;
    $count = 0;

    while ($i < count($debtors) && $j < count($creditors)) {
        $amount = min($debtors[$i], $creditors[$j]);
        $debtors[$i] -= $amount;
        $creditors[$j] -= $amount;
        $count++;

        if ($debtors[$i] === 0) {
            $i++;
        }
        if ($creditors[$j] === 0) {
            $j++;
        }
    }

    return $count;
}

/** @param list<int> $nets */
function playersFromNets(array $nets): array
{
    return array_values(array_map(fn (int $i, int $net) => ['id' => $i + 1, 'net' => $net], array_keys($nets), $nets));
}

function assertReconciles(array $players, SettlementResult $result): void
{
    $received = [];
    $paid = [];

    foreach ($result->transfers as $t) {
        expect($t->amount)->toBeGreaterThan(0);
        expect($t->from)->not->toBe($t->to);
        $paid[$t->from] = ($paid[$t->from] ?? 0) + $t->amount;
        $received[$t->to] = ($received[$t->to] ?? 0) + $t->amount;
    }

    foreach ($players as $p) {
        $net = ($received[$p['id']] ?? 0) - ($paid[$p['id']] ?? 0);
        expect($net)->toBe($p['net']);
    }
}

it('T-1: produces the spec\'s 4-transfer optimum, not greedy\'s 5', function () {
    // requirement.md §8.2: A+6 B+4 C+1 D-5 E-3 F-3
    $players = playersFromNets([6, 4, 1, -5, -3, -3]);

    $result = Settler::settle($players);

    expect($result->transfers)->toHaveCount(4);
    expect($result->approximate)->toBeFalse();
    assertReconciles($players, $result);

    expect(greedyTransferCount($players))->toBe(5);
});

it('T-4: an all-pairs table needs exactly n/2 transfers', function (int $n) {
    $nets = [];
    for ($i = 0; $i < $n; $i++) {
        $nets[] = $i % 2 === 0 ? 100 : -100;
    }

    $players = playersFromNets($nets);
    $result = Settler::settle($players);

    expect($result->transfers)->toHaveCount(intdiv($n, 2));
    assertReconciles($players, $result);
})->with([4, 6, 10, 14]);

it('drops zero-net players entirely', function () {
    $players = playersFromNets([50, -50, 0, 0]);

    $result = Settler::settle($players);

    expect($result->transfers)->toHaveCount(1);
    assertReconciles($players, $result);
});

it('returns no transfers when every net is zero', function () {
    $result = Settler::settle(playersFromNets([0, 0, 0]));

    expect($result->transfers)->toBe([]);
    expect($result->approximate)->toBeFalse();
});

it('is deterministic: the same input always produces the same transfer list', function () {
    $players = playersFromNets([6, 4, 1, -5, -3, -3]);

    $a = Settler::settle($players);
    $b = Settler::settle($players);

    $encode = fn ($r) => array_map(fn ($t) => [$t->from, $t->to, $t->amount], $r->transfers);

    expect($encode($a))->toBe($encode($b));
});

it('rejects nets that do not sum to zero', function () {
    Settler::settle(playersFromNets([10, -5]));
})->throws(InvalidArgumentException::class);

it('falls back to greedy and flags the result as approximate above 15 players', function () {
    // 16 non-zero, non-cancelling nets summing to zero.
    $nets = range(1, 16);
    $nets[15] = -array_sum(array_slice($nets, 0, 15));
    $players = playersFromNets($nets);

    $result = Settler::settle($players);

    expect($result->approximate)->toBeTrue();
    assertReconciles($players, $result);
});

it('T-2/T-3: property test over 1000 random balanced tables of 3-12 players', function () {
    mt_srand(20260911);
    $maxMs = 0.0;

    for ($iter = 0; $iter < 1000; $iter++) {
        $n = mt_rand(3, 12);
        $nets = [];
        $sum = 0;

        for ($i = 0; $i < $n - 1; $i++) {
            $v = mt_rand(-500, 500);
            $nets[] = $v;
            $sum += $v;
        }
        $nets[] = -$sum;

        $players = playersFromNets($nets);

        $start = microtime(true);
        $result = Settler::settle($players);
        $maxMs = max($maxMs, (microtime(true) - $start) * 1000);

        expect($result->approximate)->toBeFalse();
        assertReconciles($players, $result);

        $exactCount = count($result->transfers);
        $greedyCount = greedyTransferCount($players);

        expect($exactCount)->toBeLessThanOrEqual($greedyCount);

        // Cross-check against an independently-implemented brute force,
        // for the sizes small enough to afford it.
        if ($n <= 8) {
            $nonZero = array_values(array_filter($nets, fn ($v) => $v !== 0));
            $expectedGroups = bruteForceMaxGroups($nonZero);
            $achievedGroups = count($nonZero) - $exactCount;

            expect($achievedGroups)->toBe($expectedGroups);
        }
    }

    expect($maxMs)->toBeLessThan(500.0);
});

it('settles n=15 within the NFR-11 500ms budget, worst case (no cancellable pairs)', function () {
    mt_srand(7);
    $n = 15;
    $nets = [];
    $sum = 0;

    for ($i = 0; $i < $n - 1; $i++) {
        $v = mt_rand(-999_999, 999_999);
        $nets[] = $v;
        $sum += $v;
    }
    $nets[] = -$sum;

    $players = playersFromNets($nets);

    $start = microtime(true);
    $result = Settler::settle($players);
    $elapsedMs = (microtime(true) - $start) * 1000;

    expect($result->approximate)->toBeFalse();
    assertReconciles($players, $result);
    expect($elapsedMs)->toBeLessThan(500.0);
});

it('breaks ties deterministically by player index within a group', function () {
    // Two identical +50/-50 pairs: whichever debtor/creditor comes first
    // by index should be the one paired together, every time.
    $players = playersFromNets([50, 50, -50, -50]);

    $result = Settler::settle($players);

    expect($result->transfers)->toHaveCount(2);
    expect($result->transfers[0]->from)->toBe(3); // first -50 (index 2 -> id 3)
    expect($result->transfers[0]->to)->toBe(1);   // first +50 (index 0 -> id 1)
    expect($result->transfers[1]->from)->toBe(4);
    expect($result->transfers[1]->to)->toBe(2);
});
