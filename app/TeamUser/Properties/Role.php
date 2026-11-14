<?php

declare(strict_types=1);

namespace App\TeamUser\Properties;

use App\TeamUser\TeamRoleEnum;
use Mom\Data\AbstractEnum;

final class Role extends AbstractEnum
{
    public static function getName(): string
    {
        return 'role';
    }

    public static function forEloquentFactoryValue(): string
    {
        return TeamRoleEnum::Member->value;
    }

    public function toNullableEnum(): ?TeamRoleEnum
    {
        $value = $this->toValue();

        if ($value instanceof TeamRoleEnum) {
            return $value;
        }

        return is_string($value) ? TeamRoleEnum::tryFrom($value) : null;
    }

    public function toEnum(): TeamRoleEnum
    {
        return $this->toNullableEnum() ?? TeamRoleEnum::Member;
    }
}
