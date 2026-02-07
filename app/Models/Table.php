<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function getBankAttribute(): float
    {
        $totalBuyIns = $this->buyIns()->sum('amount');
        $totalPaybacks = $this->paybacks()->sum('amount');

        return (float) ($totalBuyIns - $totalPaybacks);
    }
}
