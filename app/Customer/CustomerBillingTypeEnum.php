<?php

declare(strict_types=1);

namespace App\Customer;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * How a customer is billed. Automatic and manual customers are billable;
 * excluded ones are left out of billing on purpose.
 */
enum CustomerBillingTypeEnum: string
{
    use Enum;
    use EnumOptions;

    case Automatic = 'automatic';
    case Manual = 'manual';
    case Excluded = 'excluded';

    public function label(): string
    {
        return match ($this) {
            self::Automatic => 'Automatic',
            self::Manual => 'Manual',
            self::Excluded => 'Excluded',
        };
    }
}
