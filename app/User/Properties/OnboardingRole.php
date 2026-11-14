<?php

declare(strict_types=1);

namespace App\User\Properties;

use App\User\OnboardingRoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mom\Data\AbstractEnum;

final class OnboardingRole extends AbstractEnum
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'onboarding_role';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', Rule::enum(OnboardingRoleEnum::class)];
    }

    public static function forEloquentFactoryValue(): ?string
    {
        return null;
    }

    public function toNullableEnum(): ?OnboardingRoleEnum
    {
        $value = $this->toValue();

        if ($value instanceof OnboardingRoleEnum) {
            return $value;
        }

        return is_string($value) ? OnboardingRoleEnum::tryFrom($value) : null;
    }

    public function toEnum(): OnboardingRoleEnum
    {
        return $this->toNullableEnum() ?? OnboardingRoleEnum::cases()[0];
    }
}
