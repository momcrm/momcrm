<?php

declare(strict_types=1);

namespace App\LeaveRequest;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * How much of each day the leave takes. Half days are for one-day requests.
 */
enum LeavePartEnum: string
{
    use Enum;
    use EnumOptions;

    case Afternoon = 'afternoon';
    case Full = 'full';
    case Morning = 'morning';

    public function label(): string
    {
        return match ($this) {
            self::Afternoon => 'Afternoon',
            self::Full => 'Full day',
            self::Morning => 'Morning',
        };
    }

    public function isHalfDay(): bool
    {
        return $this !== self::Full;
    }
}
