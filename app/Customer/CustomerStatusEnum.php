<?php

declare(strict_types=1);

namespace App\Customer;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

enum CustomerStatusEnum: string
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
