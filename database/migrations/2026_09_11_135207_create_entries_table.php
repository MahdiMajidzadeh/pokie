<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            // Denormalised alongside player_id for fast table-wide totals
            // without joining through players (see requirement.md §9).
            $table->foreignId('table_id')->constrained('tables')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->enum('type', ['buy_in', 'cash_out']);
            $table->unsignedBigInteger('amount');
            $table->string('note', 120)->nullable();
            $table->timestamps();

            $table->index(['table_id', 'player_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entries');
    }
};
