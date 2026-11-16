<?php

declare(strict_types=1);

namespace App\TeamInvitation\FormRequests;

use App\Team\Actions\FindTeam;
use App\Team\Team;
use App\TeamInvitation\Actions\CreateTeamInvitation;
use App\TeamInvitation\Actions\FindTeamInvitation;
use App\TeamInvitation\Exceptions\CouldNotFindTeamInvitation;
use App\TeamInvitation\TeamInvitation;
use App\User\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Emails a pending invitation again with a new link, keeping its role and
 * group. The old link stops working and the 7 days start over.
 */
final class ResendTeamInvitationFormRequest extends FormRequest
{
    public Team $team;

    public TeamInvitation $invitation;

    public function __construct()
    {
        parent::__construct();

        $this->team = Team::new();
        $this->invitation = TeamInvitation::new();
    }

    /**
     * @throws CouldNotFindTeamInvitation
     */
    public function authorize(): Response
    {
        $this->team = FindTeam::byIdForMember(
            id: $this->user()?->current_team_id,
            userId: (string) $this->user()?->id,
        );

        $this->invitation = FindTeamInvitation::byIdForTeam(
            id: (string) $this->route('invitation'),
            teamId: $this->team->getId()->toString(),
        );

        if ($this->team->isNull() || $this->invitation->isNull() || $this->invitation->getAcceptedAt()->isNotNull()) {
            throw CouldNotFindTeamInvitation::notFound();
        }

        return Gate::authorize('invite', $this->team);
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
        CreateTeamInvitation::forTeam(
            team: $this->team,
            email: $this->invitation->getEmail()->toString(),
            role: $this->invitation->getRole()->toEnum(),
            inviter: User::fromEloquentModel($this->user()),
            groupId: $this->invitation->getGroupId()->toNullableString(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invite resent to :email.', ['email' => $this->invitation->getEmail()->toString()])]);

        return back();
    }
}
