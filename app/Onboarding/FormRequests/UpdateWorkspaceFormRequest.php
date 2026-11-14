<?php

declare(strict_types=1);

namespace App\Onboarding\FormRequests;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Team\Actions\FindTeam;
use App\Team\Actions\UpdateTeam;
use App\Team\Exceptions\CouldNotUpdateTeam;
use App\Team\Properties\Name;
use App\Team\Team;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class UpdateWorkspaceFormRequest extends FormRequest
{
    public Team $team;

    public function __construct()
    {
        parent::__construct();

        $this->team = Team::new();
    }

    /**
     * @throws CouldNotUpdateTeam
     */
    public function authorize(): Response
    {
        $this->team = FindTeam::byIdForMember(
            id: $this->user()?->current_team_id,
            userId: (string) $this->user()?->getAuthIdentifier(),
        );

        if ($this->team->isNull()) {
            throw CouldNotUpdateTeam::notFound();
        }

        return Gate::authorize('update', $this->team);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Name::getNameForValidation() => Name::getValidationRulesForUpdate($this),
        ];
    }

    public function process(): RedirectResponse
    {
        $previousName = $this->team->getName()->toString();

        UpdateTeam::fromTeam($this->team->setPropertyIfExists($this->validated()));

        if ($this->team->getName()->toString() !== $previousName) {
            RecordActivity::for(
                type: ActivityTypeEnum::TeamRenamed,
                userId: (string) $this->user()?->getAuthIdentifier(),
                teamId: $this->team->getId()->toString(),
                details: ['from' => $previousName, 'to' => $this->team->getName()->toString()],
            );
        }

        return to_route('onboarding.invite');
    }
}
