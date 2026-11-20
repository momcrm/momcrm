<?php

declare(strict_types=1);

namespace App\PaymentAllocation\Properties;

use App\Payment\Payment as PaymentData;
use App\Shared\Abstracts\AbstractRelatedData;

/**
 * The payment the allocation is part of, when loaded.
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
