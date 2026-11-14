<?php

declare(strict_types=1);

namespace App\TeamInvitation\FormRequests;

use App\Team\Actions\FindTeam;
use App\TeamInvitation\Actions\AcceptTeamInvitation;
use App\TeamInvitation\Actions\FindTeamInvitation;
use App\TeamInvitation\Exceptions\CouldNotFindTeamInvitation;
use App\TeamInvitation\TeamInvitation;
use App\User\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class AcceptTeamInvitationFormRequest extends FormRequest
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
        $this->invitation = FindTeamInvitation::byToken((string) $this->route('token'));

        if (! $this->invitation->isPending()) {
            throw CouldNotFindTeamInvitation::notFound();
        }

        if (! $this->invitation->isForEmail((string) $this->user()?->email)) {
            return Response::deny(__('This invitation was sent to another email address.'));
        }

        return Response::allow();
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
        AcceptTeamInvitation::forUser($this->invitation, User::fromEloquentModel($this->user()));

        $team = FindTeam::byId($this->invitation->getTeamId()->toString());

        Inertia::flash('toast', ['type' => 'success', 'message' => __("You've joined :team.", ['team' => $team->getName()->toString()])]);

        return to_route('dashboard');
    }
}
