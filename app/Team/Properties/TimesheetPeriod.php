<?php

declare(strict_types=1);

namespace App\Team\Properties;

use App\Timesheet\TimesheetPeriodEnum;
use Mom\Data\AbstractEnum;

/**
 * How long the team's pay periods are, weekly unless changed.
 */
final class TimesheetPeriod extends AbstractEnum
{
    public static bool $includeInDatabaseCreate = false;

    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'timesheet_period';
    }

    public static function forEloquentFactoryValue(): string
    {
        return TimesheetPeriodEnum::Weekly->value;
    }

    public function toNullableEnum(): ?TimesheetPeriodEnum
    {
        $value = $this->toValue();

        if ($value instanceof TimesheetPeriodEnum) {
            return $value;
        }

        return is_string($value) ? TimesheetPeriodEnum::tryFrom($value) : null;
    }

    public function toEnum(): TimesheetPeriodEnum
    {
        return $this->toNullableEnum() ?? TimesheetPeriodEnum::Weekly;
    }
}
