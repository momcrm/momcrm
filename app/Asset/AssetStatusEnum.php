<?php

declare(strict_types=1);

namespace App\Asset;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

enum AssetStatusEnum: string
{
    use Enum;
    use EnumOptions;

    case Active = 'active';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Retired => 'Retired',
        };
    }
}
