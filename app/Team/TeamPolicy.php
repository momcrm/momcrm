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
        return $this->isOwnerOrAdmin($user, $team)
            ? Response::allow()
            : Response::deny('Only team owners and admins can invite people.');
    }

    /**
     * Owners and admins can remove the sample data, which deletes records across the team.
     */
    public function removeSampleData(UserEloquentModel $user, Team $team): Response
    {
        return $this->isOwnerOrAdmin($user, $team)
            ? Response::allow()
            : Response::deny('Only team owners and admins can remove the sample data.');
    }

    /**
     * Owners and admins write the team's access rules. This is not itself an
     * access rule, so nobody can rule themselves out of fixing the rules.
     */
    public function manageAccessRules(UserEloquentModel $user, Team $team): Response
    {
        return $this->isOwnerOrAdmin($user, $team)
            ? Response::allow()
            : Response::deny('Only team owners and admins can manage access rules.');
    }

    private function isOwnerOrAdmin(UserEloquentModel $user, Team $team): bool
    {
        $role = FindTeamUser::byTeamAndUser(
            teamId: $team->getId()->toString(),
            userId: $user->id,
        )->getRole()->toNullableEnum();

        return in_array($role, [TeamRoleEnum::Owner, TeamRoleEnum::Admin], true);
    }
}
