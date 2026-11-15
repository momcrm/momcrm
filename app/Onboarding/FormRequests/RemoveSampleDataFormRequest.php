<?php

declare(strict_types=1);

namespace App\Onboarding\FormRequests;

use App\Onboarding\Actions\RemoveSampleData;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotUpdateTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class RemoveSampleDataFormRequest extends FormRequest
{
    public function authorize(): Response
    {
        $team = FindTeam::byIdForMember(
            id: $this->user()?->current_team_id,
            userId: (string) $this->user()?->id,
        );

        if ($team->isNull()) {
            throw CouldNotUpdateTeam::notFound();
        }

        return Gate::authorize('removeSampleData', $team);
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
            userId: (string) $this->user()?->id,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sample data removed. The workspace is all yours.')]);

        return to_route('customers.index');
    }
}
