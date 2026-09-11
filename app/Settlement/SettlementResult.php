<?php

declare(strict_types=1);

namespace App\Settlement;

final class SettlementResult
{
    /**
     * @param  list<Transfer>  $transfers
     * @param  bool  $approximate  true when the input exceeded the exact solver's
     *                             player cap (requirement.md §8.3 Step 5) and a
     *                             plain greedy fallback was used instead — the
     *                             transfer count may not be minimal.
     */
    public function __construct(
        public readonly array $transfers,
        public readonly bool $approximate,
    ) {}
}
