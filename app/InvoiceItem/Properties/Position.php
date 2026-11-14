<?php

declare(strict_types=1);

namespace App\InvoiceItem\Properties;

use Mom\Data\AbstractInteger;

final class Position extends AbstractInteger
{
    public static function getName(): string
    {
        return 'position';
    }
}
