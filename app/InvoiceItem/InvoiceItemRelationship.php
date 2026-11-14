<?php

declare(strict_types=1);

namespace App\InvoiceItem;

use App\Shared\Traits\Enum;

enum InvoiceItemRelationship: string
{
    use Enum;

    case Invoice = 'invoice';
}
