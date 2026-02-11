<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Table extends Model
{
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
        return (float) ($this->paybacks()->sum('amount'));
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
        $debt = [];
        $playerMap = [];
        foreach ($players as $p) {
            $bal = $p->display_amount;
            if (abs($bal) > 0.001) {
                $debt[] = $bal;
                $playerMap[] = $p;
            }
        }

        $bestTransactions = [];
        $minCount = PHP_INT_MAX;
        $this->dfsMinTransactions($debt, 0, [], $playerMap, $bestTransactions, $minCount);

        return collect($bestTransactions);
    }

    /**
     * Backtracking DFS to find minimum number of transactions.
     *
     * @param  array<int, float>  $debt
     * @param  array<int, object{from: Player, to: Player, amount: float}>  $current
     * @param  array<int, Player>  $playerMap
     * @param  array<int, object{from: Player, to: Player, amount: float}>  $bestTransactions
     */
    private function dfsMinTransactions(
        array $debt,
        int $s,
        array $current,
        array $playerMap,
        array &$bestTransactions,
        int &$minCount
    ): void {
        $n = count($debt);
        while ($s < $n && abs($debt[$s]) < 0.001) {
            $s++;
        }

        if ($s >= $n) {
            if (count($current) < $minCount) {
                $minCount = count($current);
                $bestTransactions = $current;
            }
            return;
        }

        $prev = 0.0;
        for ($i = $s + 1; $i < $n; $i++) {
            if (abs($debt[$i]) < 0.001) {
                continue;
            }
            if ($debt[$s] * $debt[$i] >= 0) {
                continue;
            }
            if (abs($debt[$i] - $prev) < 0.001) {
                continue;
            }
            if (count($current) + 1 >= $minCount) {
                continue;
            }
            $transfer = min(abs($debt[$s]), abs($debt[$i]));
            $debt[$i] += $debt[$s];
            $debtor = $debt[$s] < 0 ? $playerMap[$s] : $playerMap[$i];
            $creditor = $debt[$s] < 0 ? $playerMap[$i] : $playerMap[$s];
            $current[] = (object) [
                'from' => $debtor,
                'to' => $creditor,
                'amount' => round($transfer, 2),
            ];
            $this->dfsMinTransactions($debt, $s + 1, $current, $playerMap, $bestTransactions, $minCount);
            array_pop($current);
            $debt[$i] -= $debt[$s];
            $prev = $debt[$i];
            if (abs($debt[$i]) < 0.001) {
                break;
            }
        }
    }
}
