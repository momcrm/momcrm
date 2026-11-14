<?php

declare(strict_types=1);

namespace App\Activity\Properties;

use App\Activity\ActivityTypeEnum;
use Mom\Data\AbstractEnum;

final class Type extends AbstractEnum
{
    public static function getName(): string
    {
        return 'type';
    }

    public static function forEloquentFactoryValue(): string
    {
        return ActivityTypeEnum::cases()[0]->value;
    }

    public function toNullableEnum(): ?ActivityTypeEnum
    {
        $value = $this->toValue();

        if ($value instanceof ActivityTypeEnum) {
            return $value;
        }

        return is_string($value) ? ActivityTypeEnum::tryFrom($value) : null;
    }

    public function toEnum(): ActivityTypeEnum
    {
        return $this->toNullableEnum() ?? ActivityTypeEnum::cases()[0];
    }
}
