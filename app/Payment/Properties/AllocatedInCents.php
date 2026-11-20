<?php

declare(strict_types=1);

namespace App\Payment\Properties;

use Mom\Data\AbstractInteger;

/**
 * How much of the payment has been applied to invoices: the sum of its
 * allocations, read when the payment is found. Not a column.
 */
final class AllocatedInCents extends AbstractInteger
{
    public static bool $hasDBColumn = false;

    public static function getName(): string
    {
        return 'allocated_in_cents';
    }
}
