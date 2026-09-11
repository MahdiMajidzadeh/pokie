<?php

declare(strict_types=1);

namespace App\Settlement;

/**
 * One payment instruction produced by the solver: `from` pays `to` `amount`.
 * Both `from` and `to` are player IDs.
 */
final class Transfer
{
    public function __construct(
        public readonly int $from,
        public readonly int $to,
        public readonly int $amount,
    ) {}
}
