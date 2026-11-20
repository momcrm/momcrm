<?php

declare(strict_types=1);

namespace App\PaymentAllocation;

use App\Shared\Traits\Enum;

enum PaymentAllocationRelationship: string
{
    use Enum;

    case Invoice = 'invoice';
    case Payment = 'payment';
}
