<?php

declare(strict_types=1);

namespace App\Onboarding\FormRequests;

use App\Onboarding\Actions\CompleteOnboarding;
use App\User\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class CompleteOnboardingFormRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    public function process(): RedirectResponse
    {
        CompleteOnboarding::fromUser(User::fromEloquentModel($this->user()));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Your workspace is ready.')]);

        return to_route('dashboard');
    }
}
