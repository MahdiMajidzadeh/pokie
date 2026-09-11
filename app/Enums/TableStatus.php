<?php

declare(strict_types=1);

namespace App\Enums;

enum TableStatus: string
{
    case Open = 'open';
    case Settled = 'settled';
}
