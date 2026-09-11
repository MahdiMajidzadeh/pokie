<?php

declare(strict_types=1);

// AR-2: both admin env vars are surfaced here (never call env() outside
// config files) so config caching works in production.
return [

    'admin' => [
        'username' => env('PTABLE_ADMIN_USERNAME'),
        'password' => env('PTABLE_ADMIN_PASSWORD'),

        // AR-7: shorter than the app's own session lifetime (config/session.php).
        'session_timeout_minutes' => 120,

        // AR-16: 5 attempts / 15 minutes, then a 1-hour lockout.
        'login_max_attempts' => 5,
        'login_decay_minutes' => 15,
        'login_lockout_minutes' => 60,
    ],

    // NFR-7: input validation ceiling for any amount (buy-in, cash-out, default buy-in).
    'amount_max' => 999_999_999_999,

    // NFR-2: rate limits.
    'write_rate_limit_per_minute' => 60,
    'create_rate_limit_per_hour' => 10,

    // FR-23: Livewire polling interval while a table is open.
    'poll_seconds' => 5,

];
