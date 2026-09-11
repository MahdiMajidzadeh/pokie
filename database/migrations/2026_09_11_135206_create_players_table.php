<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained('tables')->cascadeOnDelete();
            $table->string('name', 40);
            $table->boolean('has_left')->default(false);
            $table->smallInteger('position');
            $table->timestamps();

            // MySQL's default collation (utf8mb4_0900_ai_ci) is case-insensitive,
            // so this unique index already enforces FR-6 ("unique per table,
            // case-insensitive"); the app also validates it explicitly so the
            // error message is friendly rather than a raw DB constraint violation.
            $table->unique(['table_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
