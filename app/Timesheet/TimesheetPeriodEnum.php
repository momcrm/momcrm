<?php

declare(strict_types=1);

namespace App\Timesheet;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * How long a team's pay periods are. Both start on a Monday.
 */
enum TimesheetPeriodEnum: string
{
    use Enum;
    use EnumOptions;

    case Fortnightly = 'fortnightly';
    case Weekly = 'weekly';

    public function label(): string
    {
        return match ($this) {
            self::Fortnightly => 'Fortnightly',
            self::Weekly => 'Weekly',
        };
    }

    public function days(): int
    {
        return match ($this) {
            self::Fortnightly => 14,
            self::Weekly => 7,
        };
    }
}
