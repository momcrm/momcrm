<?php

declare(strict_types=1);

namespace App\TeamInvitation\Actions;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Team\Actions\FindTeam;
use App\Team\Actions\SwitchCurrentTeam;
use App\TeamInvitation\TeamInvitation;
use App\TeamInvitation\TeamInvitationEloquentModel;
use App\TeamUser\Actions\CreateTeamUser;
use App\TeamUser\Actions\FindTeamUser;
use App\User\Actions\UpdateUser;
use App\User\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Adds the user to the invitation's team and makes it their current team.
 *
 * Someone joining an existing team skips the workspace onboarding steps.
 */
final readonly class AcceptTeamInvitation
{
    public function __construct(
        private TeamInvitation $invitation,
        private User $user,
    ) {}

    public function __invoke(): User
    {
        return DB::transaction(function (): User {
            $teamId = $this->invitation->getTeamId()->toString();
            $userId = $this->user->getId()->toString();

            if (FindTeamUser::byTeamAndUser(teamId: $teamId, userId: $userId)->isNull()) {
                CreateTeamUser::fromIds(teamId: $teamId, userId: $userId, role: $this->invitation->getRole()->toEnum());
            }

            TeamInvitationEloquentModel::query()
                ->withoutGlobalScopes()
                ->findOrFail($this->invitation->getId()->toString())
                ->update($this->invitation->setAcceptedAt(CarbonImmutable::now())->forDatabaseUpdate());

            if ($this->user->getOnboardedAt()->isNull()) {
                UpdateUser::fromUser($this->user->setOnboardedAt(CarbonImmutable::now()));
            }

            SwitchCurrentTeam::fromTeam(user: $this->user, team: FindTeam::byId($teamId));

            RecordActivity::for(type: ActivityTypeEnum::InvitationAccepted, userId: $userId, teamId: $teamId);

            return $this->user;
        });
    }

    public static function forUser(TeamInvitation $invitation, User $user): User
    {
        return new self($invitation, $user)->handle();
    }

    public function handle(): User
    {
        return $this->__invoke();
    }

    public function execute(): User
    {
        return $this->__invoke();
    }
}
