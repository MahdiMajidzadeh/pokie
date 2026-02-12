<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('poker_tables', function (Blueprint $table): void {
            $table->index('created_at');
        });

        Schema::table('buy_ins', function (Blueprint $table): void {
            $table->index('created_at');
        });

        Schema::table('paybacks', function (Blueprint $table): void {
            $table->index('created_at');
        });

        Schema::table('settlements', function (Blueprint $table): void {
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('poker_tables', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
        });

        Schema::table('buy_ins', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
        });

        Schema::table('paybacks', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
        });

        Schema::table('settlements', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
        });
    }
};
