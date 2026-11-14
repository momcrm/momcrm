<?php

declare(strict_types=1);

namespace App\Onboarding\FormRequests;

use App\Onboarding\Actions\RemoveSampleData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class RemoveSampleDataFormRequest extends FormRequest
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
        RemoveSampleData::forTeam(
            teamId: (string) $this->user()?->current_team_id,
            userId: (string) $this->user()?->getAuthIdentifier(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sample data removed. The workspace is all yours.')]);

        return to_route('customers.index');
    }
}
