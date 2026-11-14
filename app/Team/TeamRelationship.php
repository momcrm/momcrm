<?php

declare(strict_types=1);

namespace App\Team;

use App\Shared\Traits\Enum;

enum TeamRelationship: string
{
    use Enum;

    case Owner = 'owner';
    case Users = 'users';
}
