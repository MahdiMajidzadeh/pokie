<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained('tables')->cascadeOnDelete();
            $table->foreignId('from_player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('to_player_id')->constrained('players')->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['table_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
