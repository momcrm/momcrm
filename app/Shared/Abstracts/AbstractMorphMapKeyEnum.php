<?php

declare(strict_types=1);

namespace App\Shared\Abstracts;

use App\Shared\MorphMapKeyEnum;
use Mom\Data\AbstractEnum;

/**
 * A property holding a morph map alias, e.g. a polymorphic *_type column.
 */
abstract class AbstractMorphMapKeyEnum extends AbstractEnum
{
    public static function forEloquentFactoryValue(): string
    {
        return MorphMapKeyEnum::User->value;
    }

    public function toNullableEnum(): ?MorphMapKeyEnum
    {
        $value = $this->toValue();

        if ($value instanceof MorphMapKeyEnum) {
            return $value;
        }

        return is_string($value) ? MorphMapKeyEnum::tryFrom($value) : null;
    }

    public function toEnum(): MorphMapKeyEnum
    {
        return $this->toNullableEnum() ?? MorphMapKeyEnum::User;
    }
}
