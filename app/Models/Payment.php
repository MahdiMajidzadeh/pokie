<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'table_id',
        'from_player_id',
        'to_player_id',
        'amount',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function fromPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'from_player_id');
    }

    public function toPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'to_player_id');
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }
}
