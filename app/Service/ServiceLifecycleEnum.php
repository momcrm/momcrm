<?php

declare(strict_types=1);

namespace App\Service;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * Where a service is in its life, as lists show and filter it: active,
 * inactive (turned off, kept on the customer), or archived (put away).
 */
enum ServiceLifecycleEnum: string
{
    use Enum;
    use EnumOptions;

    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Archived => 'Archived',
        };
    }
}
