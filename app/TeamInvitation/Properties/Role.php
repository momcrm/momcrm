<?php

declare(strict_types=1);

namespace App\TeamInvitation\Properties;

use App\TeamUser\TeamRoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mom\Data\AbstractEnum;

final class Role extends AbstractEnum
{
    public static function getName(): string
    {
        return 'role';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', Rule::enum(TeamRoleEnum::class)->only([TeamRoleEnum::Admin, TeamRoleEnum::Member])];
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
