<?php

declare(strict_types=1);

namespace App\User\FormRequests;

use App\User\Actions\UpdateUserPassword;
use App\User\Properties\CurrentPassword;
use App\User\Properties\Password;
use App\User\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class UpdatePasswordFormRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            CurrentPassword::getNameForValidation() => CurrentPassword::getValidationRulesForCreate($this),
            Password::getNameForValidation() => Password::getValidationRulesForCreate($this),
        ];
    }

    public function process(): RedirectResponse
    {
        UpdateUserPassword::fromUser(
            User::fromEloquentModel($this->user())
                ->setPassword($this->string(Password::getNameForValidation())->toString()),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Password updated.')]);

        return back();
    }
}
