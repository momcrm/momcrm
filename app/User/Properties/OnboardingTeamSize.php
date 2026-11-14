<?php

declare(strict_types=1);

namespace App\User\Properties;

use App\User\OnboardingTeamSizeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mom\Data\AbstractEnum;

final class OnboardingTeamSize extends AbstractEnum
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'onboarding_team_size';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', Rule::enum(OnboardingTeamSizeEnum::class)];
    }

    public static function forEloquentFactoryValue(): ?string
    {
        return null;
    }

    public function toNullableEnum(): ?OnboardingTeamSizeEnum
    {
        $value = $this->toValue();

        if ($value instanceof OnboardingTeamSizeEnum) {
            return $value;
        }

        return is_string($value) ? OnboardingTeamSizeEnum::tryFrom($value) : null;
    }

    public function toEnum(): OnboardingTeamSizeEnum
    {
        return $this->toNullableEnum() ?? OnboardingTeamSizeEnum::cases()[0];
    }
}
