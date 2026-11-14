<?php

declare(strict_types=1);

namespace App\Service;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

enum ServiceStatusEnum: string
{
    use Enum;
    use EnumOptions;

    case Active = 'active';
    case Cancelled = 'cancelled';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Cancelled => 'Cancelled',
            self::Pending => 'Pending',
        };
    }
}
