<?php

declare(strict_types=1);

namespace App\Settlement;

use App\Enums\TableStatus;
use App\Models\Table;
use Illuminate\Support\Facades\DB;

/**
 * requirement.md §6: reopening clears the frozen payment list (the manager
 * is warned about this before calling here) and returns the table to open.
 */
final class ReopenTable
{
    public function __invoke(Table $table): Table
    {
        return DB::transaction(function () use ($table) {
            if (! $table->isSettled()) {
                return $table;
            }

            $table->payments()->delete();

            $table->update([
                'status' => TableStatus::Open,
                'settled_at' => null,
            ]);

            return $table;
        });
    }
}
