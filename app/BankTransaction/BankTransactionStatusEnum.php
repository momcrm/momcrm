<?php

declare(strict_types=1);

namespace App\BankTransaction;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * Where a bank feed line is up to. Pending lines are the ones still to deal
 * with; hidden ones were put aside as not a customer's payment.
 */
enum BankTransactionStatusEnum: string
{
    use Enum;
    use EnumOptions;

    case Pending = 'pending';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Hidden => 'Hidden',
        };
    }
}
