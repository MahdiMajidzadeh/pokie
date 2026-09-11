<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Generates and validates the 6-character table/manager hashes (see
 * requirement.md §4.1). Digits 0/1 and letters O/I/L are excluded from the
 * alphabet on purpose, to avoid transcription errors when a hash is read
 * aloud or copied by hand.
 */
final class Hash
{
    public const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    public const LENGTH = 6;

    private function __construct() {}

    /**
     * Route-parameter regex: {6} of the alphabet, case-insensitive (lookup
     * normalises to uppercase, so the route itself accepts either case).
     * Built from ALPHABET rather than a hand-typed character range — a
     * hand-typed range for this alphabet (23456789ABCDEFGHJKMNPQRSTUVWXYZ,
     * skipping I/L/O) is exactly the kind of thing that's easy to get
     * subtly wrong and have it silently 404 a fraction of real hashes.
     */
    public static function routePattern(): string
    {
        return '['.self::ALPHABET.strtolower(self::ALPHABET).']{'.self::LENGTH.'}';
    }

    public static function generate(): string
    {
        $alphabet = self::ALPHABET;
        $max = strlen($alphabet) - 1;

        $hash = '';
        for ($i = 0; $i < self::LENGTH; $i++) {
            $hash .= $alphabet[random_int(0, $max)];
        }

        return $hash;
    }

    /**
     * Normalises a hash to its canonical uppercase form, or returns null if
     * the value is not a syntactically valid hash. Lookup is always
     * case-insensitive (requirement.md §4.1), so every route/query that
     * accepts a hash from the outside world must go through this first.
     */
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $upper = strtoupper($value);

        if (strlen($upper) !== self::LENGTH) {
            return null;
        }

        for ($i = 0; $i < self::LENGTH; $i++) {
            if (! str_contains(self::ALPHABET, $upper[$i])) {
                return null;
            }
        }

        return $upper;
    }
}
