<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Table;
use Illuminate\Support\Facades\Cookie;

class RecentTables
{
    public const COOKIE = 'pokie_recent_tables';

    private const MAX = 10;

    /** @return array<int, array<string, mixed>> */
    public static function read(): array
    {
        return json_decode(request()->cookie(self::COOKIE, '[]'), true) ?: [];
    }

    public static function push(Table $table, ?string $managerToken = null): void
    {
        $recent = self::read();

        $entry = [
            'token' => $table->token,
            'name' => $table->name,
            'at' => now()->toIso8601String(),
        ];

        $existing = collect($recent)->first(fn ($e) => ($e['token'] ?? '') === $table->token);
        $managerToken ??= $existing['manager_token'] ?? null;
        if ($managerToken !== null) {
            $entry['manager_token'] = $managerToken;
        }

        $recent = array_values(array_filter($recent, fn ($e) => ($e['token'] ?? '') !== $table->token));
        array_unshift($recent, $entry);
        $recent = array_slice($recent, 0, self::MAX);

        Cookie::queue(self::COOKIE, json_encode($recent), 60 * 24 * 365);
    }
}
