<?php

declare(strict_types=1);

namespace App\Onboarding\Controllers;

use App\User\OnboardingRoleEnum;
use App\User\OnboardingTeamSizeEnum;
use App\User\OnboardingUseCaseEnum;
use App\User\User;
use BackedEnum;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShowAboutController
{
    public function __invoke(Request $request): Response
    {
        $user = User::fromEloquentModel($request->user());

        return Inertia::render('onboarding/about', [
            'answers' => [
                'onboarding_use_case' => $user->getOnboardingUseCase()->toNullableEnum()?->value,
                'onboarding_role' => $user->getOnboardingRole()->toNullableEnum()?->value,
                'onboarding_team_size' => $user->getOnboardingTeamSize()->toNullableEnum()?->value,
            ],
            'options' => [
                'onboarding_use_case' => self::options(OnboardingUseCaseEnum::cases()),
                'onboarding_role' => self::options(OnboardingRoleEnum::cases()),
                'onboarding_team_size' => self::options(OnboardingTeamSizeEnum::cases()),
            ],
        ]);
    }

    /**
     * @param  array<OnboardingUseCaseEnum|OnboardingRoleEnum|OnboardingTeamSizeEnum>  $cases
     * @return list<array{value: string, label: string}>
     */
    private static function options(array $cases): array
    {
        return array_values(array_map(
            fn (BackedEnum $case): array => ['value' => (string) $case->value, 'label' => $case->label()],
            $cases,
        ));
    }
}
