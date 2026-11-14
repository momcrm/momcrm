<?php

declare(strict_types=1);

namespace App\User;

use App\Shared\Traits\Enum;

enum UserRelationship: string
{
    use Enum;

    case CurrentTeam = 'currentTeam';
    case OwnedTeams = 'ownedTeams';
    case Teams = 'teams';
}
