<?php

declare(strict_types=1);

namespace App\BankAccount;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * What a bank account's files are: its statements, exported from online
 * banking as CSV or OFX.
 */
enum BankAccountKindEnum: string
{
    use Enum;
    use EnumOptions;

    case Statement = 'statement';

    public function label(): string
    {
        return match ($this) {
            self::Statement => 'Bank statement (CSV or OFX)',
        };
    }
}
