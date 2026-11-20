<?php

declare(strict_types=1);

namespace App\BankTransaction\Properties;

use App\Payment\Payment as PaymentData;
use App\Shared\Abstracts\AbstractRelatedData;

/**
 * The payment recorded from the line, when loaded.
 *
 * @extends AbstractRelatedData<PaymentData>
 */
final class Payment extends AbstractRelatedData
{
    public static function getName(): string
    {
        return 'payment';
    }

    public static function dataClass(): string
    {
        return PaymentData::class;
    }
}
