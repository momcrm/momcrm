<?php

declare(strict_types=1);

namespace App\Shared;

enum Table: string
{
    case Passkeys = 'passkeys';
    case TeamUser = 'team_user';
    case Teams = 'teams';
    case Users = 'users';
}
