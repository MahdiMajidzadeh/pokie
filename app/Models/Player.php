<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Player extends Model
{
    protected $fillable = ['table_id', 'name'];

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id');
    }

    public function buyIns(): HasMany
    {
        return $this->hasMany(BuyIn::class);
    }

    public function paybacks(): HasMany
    {
        return $this->hasMany(Payback::class);
    }

    /** Player balance: buy-ins stored negative, paybacks positive. Balance = -buyIns - paybacks. */
    public function getAmountAttribute(): float
    {
        $buyIns = $this->buyIns()->sum('amount');
        $paybacks = $this->paybacks()->sum('amount');
        // dd($buyIns, $paybacks);

        return (float) ($paybacks + $buyIns);
    }

    /** @return Collection<int, object{type: string, label: string, amount: float|string, created_at: \Illuminate\Support\Carbon}> */
    public function getRecordsAttribute(): Collection
    {
        $buyIns = $this->buyIns->map(fn (BuyIn $b) => (object) [
            'type' => 'buy_in',
            'label' => 'Buy-in',
            'amount' => $b->amount,
            'created_at' => $b->created_at,
        ]);
        $paybacks = $this->paybacks->map(fn (Payback $p) => (object) [
            'type' => 'payback',
            'label' => 'Payback',
            'amount' => $p->amount,
            'created_at' => $p->created_at,
        ]);

        return $buyIns->concat($paybacks)->sortBy('created_at')->values();
    }
}
