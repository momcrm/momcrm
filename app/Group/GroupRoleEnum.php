<?php

declare(strict_types=1);

namespace App\Group;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

enum GroupRoleEnum: string
{
    use Enum;
    use EnumOptions;

    case Lead = 'lead';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Lead => 'Lead',
            self::Member => 'Member',
        };
    }
}
