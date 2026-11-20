<?php

declare(strict_types=1);

namespace App\BankAccount;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * What a bank account's files are: its statements, exported from online
 * banking as CSV or OFX, or the BPAY biller files of the team's biller code.
 */
enum BankAccountKindEnum: string
{
    use Enum;
    use EnumOptions;

    case Statement = 'statement';
    case Bpay = 'bpay';

    public function label(): string
    {
        return match ($this) {
            self::Statement => 'Bank statement (CSV or OFX)',
            self::Bpay => 'BPAY biller file (CSV)',
        };
    }
}
