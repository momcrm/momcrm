<?php

declare(strict_types=1);

namespace App\LeaveRequest\Properties;

use App\LeaveRequest\LeavePartEnum;
use Mom\Data\AbstractEnum;

final class Part extends AbstractEnum
{
    public static function getName(): string
    {
        return 'part';
    }

    public static function forEloquentFactoryValue(): string
    {
        return LeavePartEnum::Full->value;
    }

    public function toNullableEnum(): ?LeavePartEnum
    {
        $value = $this->toValue();

        if ($value instanceof LeavePartEnum) {
            return $value;
        }

        return is_string($value) ? LeavePartEnum::tryFrom($value) : null;
    }

    public function toEnum(): LeavePartEnum
    {
        return $this->toNullableEnum() ?? LeavePartEnum::Full;
    }
}
