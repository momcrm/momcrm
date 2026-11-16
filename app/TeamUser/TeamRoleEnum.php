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

    /**
     * Owners can remove admins and members, admins can remove members, and
     * nobody can remove the owner.
     */
    public function canRemove(self $role): bool
    {
        return match ($this) {
            self::Owner => $role !== self::Owner,
            self::Admin => $role === self::Member,
            self::Member => false,
        };
    }
}
