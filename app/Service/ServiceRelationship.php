<?php

declare(strict_types=1);

namespace App\Service;

use App\Shared\Traits\Enum;

enum ServiceRelationship: string
{
    use Enum;

    case Contact = 'contact';
    case Customer = 'customer';
    case Location = 'location';
    case Rate = 'rate';
}
