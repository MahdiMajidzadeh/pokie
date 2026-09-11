<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TableStatus;
use App\Support\Hash;
use Database\Factories\TableFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class Table extends Model
{
    /** @use HasFactory<TableFactory> */
    use HasFactory;

    protected $table = 'tables';

    protected $fillable = [
        'table_hash',
        'manager_hash',
        'name',
        'default_buy_in',
        'status',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TableStatus::class,
            'default_buy_in' => 'integer',
            'settled_at' => 'datetime',
        ];
    }

    /**
     * Create a table with two freshly generated, mutually independent hashes,
     * retrying on the (astronomically unlikely) unique-index collision.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function createWithHashes(array $attributes = []): self
    {
        $attempts = 0;

        while (true) {
            $attempts++;

            try {
                return DB::transaction(fn () => self::create([
                    ...$attributes,
                    'table_hash' => Hash::generate(),
                    'manager_hash' => Hash::generate(),
                ]));
            } catch (QueryException $e) {
                if ($attempts >= 5 || ! str_contains($e->getMessage(), 'Duplicate entry')) {
                    throw $e;
                }
            }
        }
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class)->orderBy('position')->orderBy('id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isOpen(): bool
    {
        return $this->status === TableStatus::Open;
    }

    public function isSettled(): bool
    {
        return $this->status === TableStatus::Settled;
    }
}
