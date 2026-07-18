<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Table extends Model
{
    use HasFactory;

    protected $table = 'poker_tables';

    protected $fillable = ['name', 'token', 'manager_token'];

    public function players(): HasMany
    {
        return $this->hasMany(Player::class, 'table_id');
    }

    public function buyIns(): HasMany
    {
        return $this->hasMany(BuyIn::class, 'table_id');
    }

    public function paybacks(): HasMany
    {
        return $this->hasMany(Payback::class, 'table_id');
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class, 'table_id');
    }

    public function getTableBalanceAttribute(): float
    {
        return (float) $this->buyIns()->sum('amount');
    }

    /** Bank: chips still in play = -table_balance (buy-ins) - paybacks. */
    public function getBankAttribute(): float
    {
        $tableBalance = (float) $this->buyIns()->sum('amount');
        $paybacks = (float) $this->paybacks()->sum('amount');

        return -$tableBalance - $paybacks;
    }

    /**
     * Minimum transactions to settle all player balances (backtracking for true minimum).
     * From = player with negative balance (owes), To = player with positive balance (is owed).
     *
     * @return Collection<int, object{from: Player, to: Player, amount: float}>
     */
    public function getMinimumSettlementTransactions(): Collection
    {
        $players = $this->relationLoaded('players') ? $this->players : $this->players()->get();
        $balances = [];
        $playerMap = [];
        foreach ($players as $p) {
            $bal = $p->display_amount;
            if (abs($bal) > 0.001) {
                $balances[] = $bal;
                $playerMap[] = $p;
            }
        }

        $transactions = [];
        foreach ($this->partitionIntoZeroSumGroups($balances) as $group) {
            foreach ($this->settleGroup($group, $balances, $playerMap) as $tx) {
                $transactions[] = $tx;
            }
        }

        return collect($transactions);
    }

    /**
     * Partition players into the maximum number of disjoint zero-sum groups.
     * A group of size k settles in k-1 transactions, so maximizing groups
     * minimizes total transactions (n - groups).
     *
     * @param  array<int, float>  $balances
     * @return array<int, array<int, int>> groups of indices into $balances
     */
    private function partitionIntoZeroSumGroups(array $balances): array
    {
        $n = count($balances);
        if ($n === 0) {
            return [];
        }
        // Bitmask DP is exponential; past this size settle everyone as one group.
        if ($n > 16) {
            return [range(0, $n - 1)];
        }

        $full = (1 << $n) - 1;
        $bitIndex = [];
        for ($i = 0; $i < $n; $i++) {
            $bitIndex[1 << $i] = $i;
        }

        $sum = array_fill(0, $full + 1, 0.0);
        for ($mask = 1; $mask <= $full; $mask++) {
            $low = $mask & -$mask;
            $sum[$mask] = $sum[$mask ^ $low] + $balances[$bitIndex[$low]];
        }

        $best = array_fill(0, $full + 1, -1);
        $choice = array_fill(0, $full + 1, 0);
        $best[0] = 0;
        for ($mask = 1; $mask <= $full; $mask++) {
            if (abs($sum[$mask]) > 0.001) {
                continue;
            }
            $low = $mask & -$mask;
            for ($sub = $mask; $sub > 0; $sub = ($sub - 1) & $mask) {
                if (! ($sub & $low) || abs($sum[$sub]) > 0.001) {
                    continue;
                }
                $rest = $mask ^ $sub;
                if ($best[$rest] >= 0 && $best[$rest] + 1 > $best[$mask]) {
                    $best[$mask] = $best[$rest] + 1;
                    $choice[$mask] = $sub;
                }
            }
        }

        if ($best[$full] < 0) {
            return [range(0, $n - 1)];
        }

        $groups = [];
        $mask = $full;
        while ($mask !== 0) {
            $sub = $choice[$mask];
            $indices = [];
            for ($i = 0; $i < $n; $i++) {
                if ($sub & (1 << $i)) {
                    $indices[] = $i;
                }
            }
            $groups[] = $indices;
            $mask ^= $sub;
        }

        return $groups;
    }

    /**
     * Settle one zero-sum group with direct debtor-to-creditor payments.
     *
     * @param  array<int, int>  $indices
     * @param  array<int, float>  $balances
     * @param  array<int, Player>  $playerMap
     * @return array<int, object{from: Player, to: Player, amount: float}>
     */
    private function settleGroup(array $indices, array $balances, array $playerMap): array
    {
        $remaining = [];
        foreach ($indices as $i) {
            $remaining[$i] = $balances[$i];
        }
        $debtors = array_values(array_filter($indices, fn ($i) => $remaining[$i] < -0.001));
        $creditors = array_values(array_filter($indices, fn ($i) => $remaining[$i] > 0.001));

        $transactions = [];
        $d = 0;
        $c = 0;
        while ($d < count($debtors) && $c < count($creditors)) {
            $di = $debtors[$d];
            $ci = $creditors[$c];
            $transfer = min(-$remaining[$di], $remaining[$ci]);
            $transactions[] = (object) [
                'from' => $playerMap[$di],
                'to' => $playerMap[$ci],
                'amount' => round($transfer, 2),
            ];
            $remaining[$di] += $transfer;
            $remaining[$ci] -= $transfer;
            if ($remaining[$di] > -0.001) {
                $d++;
            }
            if ($remaining[$ci] < 0.001) {
                $c++;
            }
        }

        return $transactions;
    }
}
