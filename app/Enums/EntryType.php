<?php

declare(strict_types=1);

namespace App\Enums;

enum EntryType: string
{
    case BuyIn = 'buy_in';
    case CashOut = 'cash_out';
}
