<?php

declare(strict_types=1);

namespace App\Onboarding\FormRequests;

use App\Onboarding\Actions\CompleteOnboarding;
use App\Onboarding\Actions\SeedSampleData;
use App\User\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class StartWithSampleDataFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->current_team_id !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    public function process(): RedirectResponse
    {
        SeedSampleData::forTeam(
            teamId: (string) $this->user()?->current_team_id,
            userId: (string) $this->user()?->getAuthIdentifier(),
        );

        CompleteOnboarding::fromUser(User::fromEloquentModel($this->user()));

        Inertia::flash('toast', ['type' => 'success', 'message' => __("Your workspace is ready. We've added a few sample customers to explore.")]);

        return to_route('dashboard');
    }
}
