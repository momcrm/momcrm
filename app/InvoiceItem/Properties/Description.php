<?php

declare(strict_types=1);

namespace App\InvoiceItem\Properties;

use Mom\Data\AbstractString;

final class Description extends AbstractString
{
    public static function getName(): string
    {
        return 'description';
    }
}
