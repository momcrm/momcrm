<?php

declare(strict_types=1);

namespace App\Invoice;

use App\Shared\Traits\Enum;

enum InvoiceRelationship: string
{
    use Enum;

    case Allocations = 'allocations';
    case Customer = 'customer';
    case Items = 'items';
}
