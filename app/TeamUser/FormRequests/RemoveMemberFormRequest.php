<?php

declare(strict_types=1);

namespace App\TeamUser\FormRequests;

use App\Team\Actions\FindTeam;
use App\Team\Team;
use App\TeamUser\Actions\FindTeamUser;
use App\TeamUser\Actions\RemoveTeamMember;
use App\TeamUser\TeamUser;
use App\User\UserEloquentModel;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Removes someone from the current team. Owners remove admins and members,
 * admins remove members.
 */
final class RemoveMemberFormRequest extends FormRequest
{
    public Team $team;

    public TeamUser $membership;

    public function __construct()
    {
        parent::__construct();

        $this->team = Team::new();
        $this->membership = TeamUser::new();
    }

    public function authorize(): Response
    {
        $this->team = FindTeam::byIdForMember($this->user()?->current_team_id, (string) $this->user()?->id);
        $this->membership = FindTeamUser::byTeamAndUser($this->team->getId()->toString(), (string) $this->route('member'));

        if ($this->team->isNull() || $this->membership->isNull()) {
            throw new NotFoundHttpException('Member not found.');
        }

        return Gate::authorize('removeMember', [$this->team, $this->membership]);
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
        RemoveTeamMember::fromTeamUser($this->membership);

        $name = UserEloquentModel::query()->find($this->membership->getUserId()->toString())?->name;

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was removed from the team.', ['name' => $name ?? __('They')])]);

        return back();
    }
}
