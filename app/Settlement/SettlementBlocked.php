<?php

declare(strict_types=1);

namespace App\Settlement;

use RuntimeException;

/**
 * Thrown when `SettleTable` cannot settle a table: either it is unbalanced
 * (requirement.md FR-25) or it has fewer than 2 players with entries
 * (FR-24). Carries the exact user-facing message the spec requires.
 */
final class SettlementBlocked extends RuntimeException
{
    public static function unbalanced(int $imbalance): self
    {
        if ($imbalance > 0) {
            return new self(sprintf(
                "You've cashed out %s more than was bought in — check the entries.",
                number_format($imbalance)
            ));
        }

        return new self(sprintf(
            '%s is still on the table — cash out the remaining players.',
            number_format(abs($imbalance))
        ));
    }

    public static function notEnoughPlayers(): self
    {
        return new self('At least 2 players with entries are needed before you can settle.');
    }

    public static function alreadySettled(): self
    {
        return new self('This table is already settled — reopen it first.');
    }
}
