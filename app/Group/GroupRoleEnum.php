<?php

declare(strict_types=1);

namespace App\Group;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;
use Illuminate\Database\Eloquent\Model;

enum GroupRoleEnum: string
{
    use Enum;
    use EnumOptions;

    case Lead = 'lead';
    case Member = 'member';

    /**
     * The role of a member loaded through a group's members relation.
     */
    public static function ofMember(Model $member): self
    {
        $pivot = $member->getRelationValue('pivot');
        $role = $pivot instanceof Model ? $pivot->getAttribute('role') : null;

        return is_string($role) ? (self::tryFrom($role) ?? self::Member) : self::Member;
    }

    public function label(): string
    {
        return match ($this) {
            self::Lead => 'Lead',
            self::Member => 'Member',
        };
    }
}
