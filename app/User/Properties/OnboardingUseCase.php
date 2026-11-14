<?php

declare(strict_types=1);

namespace App\User\Properties;

use App\User\OnboardingUseCaseEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mom\Data\AbstractEnum;

final class OnboardingUseCase extends AbstractEnum
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'onboarding_use_case';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', Rule::enum(OnboardingUseCaseEnum::class)];
    }

    public static function forEloquentFactoryValue(): ?string
    {
        return null;
    }

    public function toNullableEnum(): ?OnboardingUseCaseEnum
    {
        $value = $this->toValue();

        if ($value instanceof OnboardingUseCaseEnum) {
            return $value;
        }

        return is_string($value) ? OnboardingUseCaseEnum::tryFrom($value) : null;
    }

    public function toEnum(): OnboardingUseCaseEnum
    {
        return $this->toNullableEnum() ?? OnboardingUseCaseEnum::cases()[0];
    }
}
