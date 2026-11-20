<?php

declare(strict_types=1);

namespace App\Payment;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Payment>
 */
final class PaymentCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Payment::class;
    }
}
