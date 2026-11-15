<?php

declare(strict_types=1);

namespace App\TeamInvitation\FormRequests;

use App\Team\Actions\FindTeam;
use App\TeamInvitation\Actions\FindTeamInvitation;
use App\TeamInvitation\Actions\RevokeTeamInvitation;
use App\TeamInvitation\Exceptions\CouldNotFindTeamInvitation;
use App\TeamInvitation\TeamInvitation;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class DeleteTeamInvitationFormRequest extends FormRequest
{
    public TeamInvitation $invitation;

    public function __construct()
    {
        parent::__construct();

        $this->invitation = TeamInvitation::new();
    }

    /**
     * @throws CouldNotFindTeamInvitation
     */
    public function authorize(): Response
    {
        $team = FindTeam::byIdForMember(
            id: $this->user()?->current_team_id,
            userId: (string) $this->user()?->id,
        );

        $this->invitation = FindTeamInvitation::byIdForTeam(
            id: (string) $this->route('invitation'),
            teamId: $team->getId()->toString(),
        );

        if ($team->isNull() || $this->invitation->isNull()) {
            throw CouldNotFindTeamInvitation::notFound();
        }

        return Gate::authorize('invite', $team);
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
        RevokeTeamInvitation::fromTeamInvitation($this->invitation);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation to :email revoked.', ['email' => $this->invitation->getEmail()->toString()])]);

        return to_route('team.edit');
    }
}
