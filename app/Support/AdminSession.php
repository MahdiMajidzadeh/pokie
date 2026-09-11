<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Session;

/**
 * The entire super-admin identity (requirement.md §4.4): two env values and
 * a session flag, nothing persisted. AR-8: this is the state the
 * `EnsureSuperAdmin` middleware and the elevation check on table routes
 * both read.
 */
final class AdminSession
{
    private function __construct() {}

    /**
     * AR-3: the whole admin feature is disabled — `/admin/*` 404s — unless
     * both env vars are configured.
     */
    public static function enabled(): bool
    {
        return filled(config('ptable.admin.username')) && filled(config('ptable.admin.password'));
    }

    /**
     * AR-7: true only while the session flag is set *and* the session has
     * been touched within the configured inactivity window. An expired
     * session is cleared immediately so a stale flag never lingers.
     */
    public static function isActive(): bool
    {
        if (! self::enabled() || ! Session::get('ptable_admin', false)) {
            return false;
        }

        $lastSeen = Session::get('ptable_admin_last_seen');
        $timeoutSeconds = ((int) config('ptable.admin.session_timeout_minutes')) * 60;

        if ($lastSeen === null || (time() - $lastSeen) > $timeoutSeconds) {
            self::logout();

            return false;
        }

        return true;
    }

    /**
     * Refreshes the inactivity clock. Call on every authenticated admin request.
     */
    public static function touch(): void
    {
        Session::put('ptable_admin_last_seen', time());
    }

    /**
     * AR-6: regenerates the session ID on login (fixation defense).
     */
    public static function login(): void
    {
        Session::regenerate();
        Session::put('ptable_admin', true);
        self::touch();
    }

    /**
     * AR-6: fully invalidates the session on logout.
     */
    public static function logout(): void
    {
        Session::invalidate();
        Session::regenerateToken();
    }

    /**
     * AR-11/AR-12/"Exit admin view" edge case: whether the admin has
     * explicitly switched a specific table to the plain viewer experience.
     */
    public static function isViewingAsViewer(string $tableHash): bool
    {
        return (bool) Session::get("ptable_admin_viewer_mode.{$tableHash}", false);
    }

    public static function toggleViewerMode(string $tableHash): void
    {
        Session::put(
            "ptable_admin_viewer_mode.{$tableHash}",
            ! self::isViewingAsViewer($tableHash)
        );
    }
}
