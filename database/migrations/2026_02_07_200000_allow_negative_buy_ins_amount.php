<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Buy-ins are stored as negative (money out). Allow signed amount and convert existing rows.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE buy_ins MODIFY amount DECIMAL(15,2) NOT NULL');
        }

        DB::table('buy_ins')->where('amount', '>', 0)->update([
            'amount' => DB::raw('-amount'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('buy_ins')->where('amount', '<', 0)->update([
            'amount' => DB::raw('-amount'),
        ]);

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE buy_ins MODIFY amount DECIMAL(15,2) UNSIGNED NOT NULL');
        }
    }
};
