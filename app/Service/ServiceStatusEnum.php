<?php

declare(strict_types=1);

namespace App\Service;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * Whether a service is on. Only active services are billed.
 */
enum ServiceStatusEnum: string
{
    use Enum;
    use EnumOptions;

    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
        };
    }
}
