<?php

declare(strict_types=1);

namespace App\Location;

use App\Shared\Traits\Enum;

enum LocationRelationship: string
{
    use Enum;

    case Customer = 'customer';
}
