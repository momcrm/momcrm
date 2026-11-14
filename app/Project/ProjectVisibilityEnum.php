<?php

declare(strict_types=1);

namespace App\Project;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * Open projects are visible to the whole team; restricted ones only to their
 * members.
 */
enum ProjectVisibilityEnum: string
{
    use Enum;
    use EnumOptions;

    case Open = 'open';
    case Restricted = 'restricted';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Everyone in the team',
            self::Restricted => 'Members only',
        };
    }
}
