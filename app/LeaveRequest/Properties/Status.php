<?php

declare(strict_types=1);

namespace App\LeaveRequest\Properties;

use App\LeaveRequest\LeaveStatusEnum;
use Mom\Data\AbstractEnum;

final class Status extends AbstractEnum
{
    public static function getName(): string
    {
        return 'status';
    }

    public static function forEloquentFactoryValue(): string
    {
        return LeaveStatusEnum::Pending->value;
    }

    public function toNullableEnum(): ?LeaveStatusEnum
    {
        $value = $this->toValue();

        if ($value instanceof LeaveStatusEnum) {
            return $value;
        }

        return is_string($value) ? LeaveStatusEnum::tryFrom($value) : null;
    }

    public function toEnum(): LeaveStatusEnum
    {
        return $this->toNullableEnum() ?? LeaveStatusEnum::Pending;
    }
}
