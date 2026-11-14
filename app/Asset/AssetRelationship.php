<?php

declare(strict_types=1);

namespace App\Asset;

use App\Shared\Traits\Enum;

enum AssetRelationship: string
{
    use Enum;

    case Customer = 'customer';
    case Location = 'location';
}
