<?php

declare(strict_types=1);

namespace App\Secret;

use App\Shared\Traits\Enum;

enum SecretRelationship: string
{
    use Enum;

    case Asset = 'asset';
    case Customer = 'customer';
}
