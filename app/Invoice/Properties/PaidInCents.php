<?php

declare(strict_types=1);

namespace App\Invoice\Properties;

use Mom\Data\AbstractInteger;

/**
 * How much of the invoice has been paid: the sum of the payments applied to
 * it, read when the invoice is found. Not a column.
 */
final class PaidInCents extends AbstractInteger
{
    public static bool $hasDBColumn = false;

    public static function getName(): string
    {
        return 'paid_in_cents';
    }
}
