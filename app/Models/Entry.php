<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EntryType;
use Database\Factories\EntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entry extends Model
{
    /** @use HasFactory<EntryFactory> */
    use HasFactory;

    protected $fillable = [
        'table_id',
        'player_id',
        'type',
        'amount',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'type' => EntryType::class,
            'amount' => 'integer',
        ];
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
