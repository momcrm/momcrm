<?php

declare(strict_types=1);

namespace App\InvoiceItem\Properties;

use Mom\Data\AbstractInteger;

final class UnitPriceInCents extends AbstractInteger
{
    public static function getName(): string
    {
        return 'unit_price_in_cents';
    }
}
