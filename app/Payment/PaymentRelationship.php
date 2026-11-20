<?php

declare(strict_types=1);

namespace App\Payment;

use App\Shared\Traits\Enum;

enum PaymentRelationship: string
{
    use Enum;

    case Allocations = 'allocations';
    case Customer = 'customer';
}
