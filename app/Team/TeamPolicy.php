<?php

declare(strict_types=1);

namespace App\Team;

use App\TeamUser\Actions\FindTeamUser;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

final class TeamPolicy
{
    use HandlesAuthorization;

    public function update(UserEloquentModel $user, Team $team): Response
    {
        if ($team->getOwnerId()->toString() === $user->getAuthIdentifier()) {
            return Response::allow();
        }

        return Response::deny('Only the team owner can update the team.');
    }

    public function viewActivity(UserEloquentModel $user, Team $team): Response
    {
        if ($team->getOwnerId()->toString() === $user->getAuthIdentifier()) {
            return Response::allow();
        }

        return Response::deny("Only the team owner can view the team's activity.");
    }

    /**
     * Owners and admins can invite people to the team and revoke invitations.
     */
    public function invite(UserEloquentModel $user, Team $team): Response
    {
        $role = FindTeamUser::byTeamAndUser(
            teamId: $team->getId()->toString(),
            userId: (string) $user->getAuthIdentifier(),
        )->getRole()->toNullableEnum();

        if (in_array($role, [TeamRoleEnum::Owner, TeamRoleEnum::Admin], true)) {
            return Response::allow();
        }

        return Response::deny('Only team owners and admins can invite people.');
    }
}
