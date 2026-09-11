<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->char('table_hash', 6)->unique();
            $table->char('manager_hash', 6)->index();
            $table->string('name', 80)->nullable();
            $table->unsignedBigInteger('default_buy_in')->nullable();
            $table->enum('status', ['open', 'settled'])->default('open');
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();

            $table->unique(['table_hash', 'manager_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
