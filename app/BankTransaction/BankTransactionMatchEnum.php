<?php

declare(strict_types=1);

namespace App\BankTransaction;

use App\Shared\Traits\Enum;

/**
 * How a bank feed line's customer was found.
 */
enum BankTransactionMatchEnum: string
{
    use Enum;

    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Picked by hand',
        };
    }
}
