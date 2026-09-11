<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Table;
use Illuminate\Support\Facades\Log;

/**
 * NFR-9: admin writes must be indistinguishable from manager writes in the
 * data itself (the table's history is the table's history) — attribution
 * lives only in the application log. AR-18: every write under an admin
 * session is logged here; AR-17 covers login attempts specifically, logged
 * via {@see self::loginAttempt()}.
 */
final class AdminAudit
{
    private function __construct() {}

    /**
     * @param  array<string, mixed>  $context  e.g. ['before' => ..., 'after' => ...]
     */
    public static function log(string $action, Table $table, array $context = []): void
    {
        if (! AdminSession::isActive()) {
            return;
        }

        Log::info("admin.{$action}", [
            'table_hash' => $table->table_hash,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            ...$context,
        ]);
    }

    public static function loginAttempt(bool $success, string $username): void
    {
        Log::info($success ? 'admin.login.success' : 'admin.login.failure', [
            'username' => $username,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
