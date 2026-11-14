<?php

declare(strict_types=1);

namespace App\Onboarding\FormRequests;

use App\User\Actions\UpdateUser;
use App\User\Properties\OnboardingRole;
use App\User\Properties\OnboardingTeamSize;
use App\User\Properties\OnboardingUseCase;
use App\User\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateAboutFormRequest extends FormRequest
{
    /**
     * Every answer is optional: a skipped question is sent as null.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            OnboardingUseCase::getNameForValidation() => OnboardingUseCase::getValidationRulesForUpdate($this),
            OnboardingRole::getNameForValidation() => OnboardingRole::getValidationRulesForUpdate($this),
            OnboardingTeamSize::getNameForValidation() => OnboardingTeamSize::getValidationRulesForUpdate($this),
        ];
    }

    public function process(): RedirectResponse
    {
        UpdateUser::fromUser(
            User::fromEloquentModel($this->user())->setPropertyIfExists($this->validated()),
        );

        return to_route('onboarding.workspace');
    }
}
