<?php

declare(strict_types=1);

namespace App\Timesheet\Properties;

use App\Timesheet\TimesheetStatusEnum;
use Mom\Data\AbstractEnum;

final class Status extends AbstractEnum
{
    public static function getName(): string
    {
        return 'status';
    }

    public static function forEloquentFactoryValue(): string
    {
        return TimesheetStatusEnum::Submitted->value;
    }

    public function toNullableEnum(): ?TimesheetStatusEnum
    {
        $value = $this->toValue();

        if ($value instanceof TimesheetStatusEnum) {
            return $value;
        }

        return is_string($value) ? TimesheetStatusEnum::tryFrom($value) : null;
    }

    public function toEnum(): TimesheetStatusEnum
    {
        return $this->toNullableEnum() ?? TimesheetStatusEnum::Submitted;
    }
}
