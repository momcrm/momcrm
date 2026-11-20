<?php

declare(strict_types=1);

namespace App\PaymentAllocation;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, PaymentAllocation>
 */
final class PaymentAllocationCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return PaymentAllocation::class;
    }
}
