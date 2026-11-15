<?php

declare(strict_types=1);

namespace App\Team\FormRequests;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Team\Actions\FindTeam;
use App\Team\Actions\SwitchCurrentTeam;
use App\Team\Exceptions\CouldNotSwitchCurrentTeam;
use App\User\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class SwitchCurrentTeamFormRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'team_id' => ['required', 'uuid'],
        ];
    }

    /**
     * @throws CouldNotSwitchCurrentTeam
     */
    public function process(): RedirectResponse
    {
        $team = FindTeam::byIdForMember(
            id: $this->string('team_id')->toString(),
            userId: (string) $this->user()?->id,
        );

        if ($team->isNull()) {
            throw CouldNotSwitchCurrentTeam::notFound();
        }

        $user = SwitchCurrentTeam::fromTeam(user: User::fromEloquentModel($this->user()), team: $team);

        RecordActivity::for(
            type: ActivityTypeEnum::TeamSwitched,
            userId: $user->getId()->toString(),
            teamId: $team->getId()->toString(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Now working in :team.', ['team' => $team->getName()->toString()])]);

        return back();
    }
}
