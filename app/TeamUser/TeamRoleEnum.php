<?php

declare(strict_types=1);

namespace App\TeamUser;

use App\Shared\Traits\Enum;

enum TeamRoleEnum: string
{
    use Enum;

    case Admin = 'admin';
    case Member = 'member';
    case Owner = 'owner';
}
