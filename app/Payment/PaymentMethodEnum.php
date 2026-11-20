<?php

declare(strict_types=1);

namespace App\Payment;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * How a payment was made. A credit adjustment is credit given to the
 * customer with no money received, such as a refund for a price change.
 */
enum PaymentMethodEnum: string
{
    use Enum;
    use EnumOptions;

    case BankTransfer = 'bank_transfer';
    case Bpay = 'bpay';
    case Card = 'card';
    case Cash = 'cash';
    case Cheque = 'cheque';
    case Credit = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::BankTransfer => 'Bank transfer (EFT)',
            self::Bpay => 'BPAY',
            self::Card => 'Card',
            self::Cash => 'Cash',
            self::Cheque => 'Cheque',
            self::Credit => 'Credit adjustment',
        };
    }
}
