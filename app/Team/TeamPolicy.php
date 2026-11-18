<?php

declare(strict_types=1);

namespace App\Team;

use App\TeamUser\Actions\FindTeamUser;
use App\TeamUser\TeamRoleEnum;
use App\TeamUser\TeamUser;
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

    /**
     * Owners and admins edit the team's customisable lists, such as ticket
     * statuses and priorities.
     */
    public function manageWorkflow(UserEloquentModel $user, Team $team): Response
    {
        return $this->isOwnerOrAdmin($user, $team)
            ? Response::allow()
            : Response::deny('Only team owners and admins can change ticket statuses and priorities.');
    }

    /**
     * Owners and admins define the fields the team adds to its records.
     */
    public function manageCustomFields(UserEloquentModel $user, Team $team): Response
    {
        return $this->isOwnerOrAdmin($user, $team)
            ? Response::allow()
            : Response::deny('Only team owners and admins can change custom fields.');
    }

    /**
     * Owners and admins define the team's own kinds of record.
     */
    public function manageCustomObjects(UserEloquentModel $user, Team $team): Response
    {
        return $this->isOwnerOrAdmin($user, $team)
            ? Response::allow()
            : Response::deny('Only team owners and admins can change custom objects.');
    }

    /**
     * Owners and admins connect the mailboxes whose mail becomes tickets.
     */
    public function manageEmail(UserEloquentModel $user, Team $team): Response
    {
        return $this->isOwnerOrAdmin($user, $team)
            ? Response::allow()
            : Response::deny('Only team owners and admins can connect mailboxes.');
    }

    /**
     * Owners remove admins and members, admins remove members. Nobody removes
     * the owner or themselves.
     */
    public function removeMember(UserEloquentModel $user, Team $team, TeamUser $membership): Response
    {
        $role = FindTeamUser::byTeamAndUser(
            teamId: $team->getId()->toString(),
            userId: $user->id,
        )->getRole()->toNullableEnum();
        $memberRole = $membership->getRole()->toNullableEnum();

        return $role !== null
            && $memberRole !== null
            && $membership->getUserId()->toString() !== $user->id
            && $role->canRemove($memberRole)
            ? Response::allow()
            : Response::deny('You can’t remove this person from the team.');
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
