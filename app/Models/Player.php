<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function getAmountAttribute(): float
    {
        $buyIns = $this->buyIns()->sum('amount');
        $paybacks = $this->paybacks()->sum('amount');

        return (float) ($buyIns - $paybacks);
    }
}
