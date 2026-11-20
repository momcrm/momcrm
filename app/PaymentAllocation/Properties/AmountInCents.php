<?php

declare(strict_types=1);

namespace App\PaymentAllocation\Properties;

use Mom\Data\AbstractInteger;

final class AmountInCents extends AbstractInteger
{
    public static function getName(): string
    {
        return 'amount_in_cents';
    }
}
