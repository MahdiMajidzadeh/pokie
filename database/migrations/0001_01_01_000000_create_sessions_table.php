<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * pTable has no users table by design (see requirement.md §9 "No users
     * table") — the super admin is two env values and a session flag, never
     * an Eloquent model. `user_id` stays in this table anyway: Laravel's
     * built-in DatabaseSessionHandler unconditionally writes it whenever a
     * Guard is bound in the container (true for every Laravel app,
     * regardless of whether config/auth.php exists), so dropping the
     * column breaks every session write with a "column not found" error.
     * It carries no foreign key — nothing ever populates it — and no app
     * code ever reads it.
     */
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
