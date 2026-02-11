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
     * Minimum transactions to settle all player balances.
     * From = player with negative balance (owes), To = player with positive balance (is owed).
     *
     * @return Collection<int, object{from: Player, to: Player, amount: float}>
     */
    public function getMinimumSettlementTransactions(): Collection
    {
        $players = $this->relationLoaded('players') ? $this->players : $this->players()->get();
        $debtors = $players->filter(fn (Player $p) => $p->display_amount < -0.001)
            ->map(fn (Player $p) => ['player' => $p, 'amount' => -$p->display_amount])
            ->sortByDesc('amount')
            ->values()
            ->all();
        $creditors = $players->filter(fn (Player $p) => $p->display_amount > 0.001)
            ->map(fn (Player $p) => ['player' => $p, 'amount' => $p->display_amount])
            ->sortByDesc('amount')
            ->values()
            ->all();

        $transactions = [];
        $di = 0;
        $ci = 0;
        while ($di < count($debtors) && $ci < count($creditors)) {
            $transfer = min($debtors[$di]['amount'], $creditors[$ci]['amount']);
            if ($transfer > 0.001) {
                $transactions[] = (object) [
                    'from' => $debtors[$di]['player'],
                    'to' => $creditors[$ci]['player'],
                    'amount' => round($transfer, 2),
                ];
            }
            $debtors[$di]['amount'] -= $transfer;
            $creditors[$ci]['amount'] -= $transfer;
            if ($debtors[$di]['amount'] < 0.001) {
                $di++;
            }
            if ($creditors[$ci]['amount'] < 0.001) {
                $ci++;
            }
        }

        return collect($transactions);
    }
}
