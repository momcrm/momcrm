<?php

declare(strict_types=1);

namespace App\TeamUser\Controllers;

use App\Activity\ActivityEloquentModel;
use App\Activity\Properties\TeamId as ActivityTeamId;
use App\Activity\Properties\UserId as ActivityUserId;
use App\Group\GroupEloquentModel;
use App\Group\GroupRelationship;
use App\Group\Properties\Name as GroupName;
use App\Group\Properties\TeamId as GroupTeamId;
use App\Project\ProjectStatusEnum;
use App\Shared\Table;
use App\Shared\Types;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use App\TeamInvitation\Actions\FindTeamInvitation;
use App\TeamInvitation\TeamInvitation;
use App\TeamUser\Actions\FindTeamUser;
use App\TeamUser\Properties\Role;
use App\TeamUser\Properties\TeamId;
use App\TeamUser\Properties\UserId;
use App\TeamUser\TeamRoleEnum;
use App\Ticket\TicketStatusCategoryEnum;
use App\User\Properties\Email;
use App\User\Properties\Id;
use App\User\Properties\Name;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The people in the team with their role, groups and open work, pending
 * invitations, and the team's groups.
 */
final class ListMembersController
{
    /**
     * @throws CouldNotViewTeam
     */
    public function __invoke(Request $request): Response
    {
        $team = FindTeam::byIdForMember($request->user()?->current_team_id, (string) $request->user()?->id);

        if ($team->isNull()) {
            throw CouldNotViewTeam::notFound();
        }

        $teamId = $team->getId()->toString();
        $canInvite = Gate::allows('invite', $team);
        $viewerId = (string) $request->user()?->id;
        $viewerRole = FindTeamUser::byTeamAndUser($teamId, $viewerId)->getRole()->toNullableEnum();

        $groupsByUser = [];

        GroupEloquentModel::query()
            ->withoutGlobalScopes()
            ->with(GroupRelationship::Members->value)
            ->where(GroupTeamId::getDatabaseTableColumnName(), $teamId)
            ->orderBy(GroupName::getDatabaseTableColumnName())
            ->get()
            ->each(function (GroupEloquentModel $group) use (&$groupsByUser): void {
                foreach ($group->members as $member) {
                    $groupsByUser[$member->id][] = ['id' => $group->id, 'name' => $group->name, 'hue' => $group->hue];
                }
            });

        $activeProjects = DB::table(Table::ProjectUser->value)
            ->join(Table::Projects->value, Table::Projects->value.'.id', '=', Table::ProjectUser->value.'.project_id')
            ->where(Table::Projects->value.'.team_id', $teamId)
            ->whereNull(Table::Projects->value.'.archived_at')
            ->where(Table::Projects->value.'.status', '!=', ProjectStatusEnum::Completed->value)
            ->selectRaw('user_id, count(*) as aggregate')
            ->groupBy('user_id')
            ->pluck('aggregate', 'user_id');

        $openTickets = DB::table(Table::Tickets->value)
            ->where('team_id', $teamId)
            ->whereNull('archived_at')
            ->whereNull('parent_id')
            ->whereIn('status_id', TicketStatusCategoryEnum::Open->statusIds())
            ->whereNotNull('assignee_id')
            ->selectRaw('assignee_id, count(*) as aggregate')
            ->groupBy('assignee_id')
            ->pluck('aggregate', 'assignee_id');

        $lastActive = ActivityEloquentModel::query()
            ->where(ActivityTeamId::getDatabaseTableColumnName(), $teamId)
            ->whereNotNull(ActivityUserId::getDatabaseTableColumnName())
            ->selectRaw('user_id, max(created_at) as last_active_at')
            ->groupBy(ActivityUserId::getDatabaseTableColumnName())
            ->toBase()
            ->pluck('last_active_at', 'user_id');

        return Inertia::render('members', [
            'teamName' => $team->getName()->toString(),
            'tab' => in_array($request->query('tab'), ['groups', 'roles'], true) ? $request->query('tab') : 'members',
            'members' => UserEloquentModel::query()
                ->join(Table::TeamUser->value, UserId::getDatabaseTableColumnNameWithTable(Table::TeamUser), '=', Id::getDatabaseTableColumnNameWithTable(Table::Users))
                ->where(TeamId::getDatabaseTableColumnNameWithTable(Table::TeamUser), $teamId)
                ->orderBy(Name::getDatabaseTableColumnNameWithTable(Table::Users))
                ->get([
                    Id::getDatabaseTableColumnNameWithTable(Table::Users),
                    Name::getDatabaseTableColumnNameWithTable(Table::Users),
                    Email::getDatabaseTableColumnNameWithTable(Table::Users),
                    Role::getDatabaseTableColumnNameWithTable(Table::TeamUser),
                ])
                ->map(fn (UserEloquentModel $member): array => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'role' => $member->getAttribute(Role::getDatabaseTableColumnName()),
                    'is_you' => $member->id === $viewerId,
                    'can_remove' => $member->id !== $viewerId
                        && $viewerRole?->canRemove(TeamRoleEnum::tryFrom(Types::string($member->getAttribute(Role::getDatabaseTableColumnName()))) ?? TeamRoleEnum::Owner) === true,
                    'groups' => $groupsByUser[$member->id] ?? [],
                    'active_projects' => Types::intOrZero($activeProjects->get($member->id)),
                    'open_tickets' => Types::intOrZero($openTickets->get($member->id)),
                    'last_active_at' => $lastActive->get($member->id) === null ? null : now()->parse(Types::string($lastActive->get($member->id)))->toIso8601String(),
                ])
                ->values(),
            'invitations' => $canInvite
                ? FindTeamInvitation::pendingForTeam($teamId)
                    ->map(fn (TeamInvitation $invitation): array => [
                        'id' => $invitation->getId()->toString(),
                        'email' => $invitation->getEmail()->toString(),
                        'role' => $invitation->getRole()->toString(),
                        'expires_at' => $invitation->getExpiresAt()->toNullableISOString(),
                        'group_id' => $invitation->getGroupId()->toNullableString(),
                    ])
                    ->values()
                    ->all()
                : [],
            'groups' => GroupEloquentModel::query()
                ->withoutGlobalScopes()
                ->where(GroupTeamId::getDatabaseTableColumnName(), $teamId)
                ->with(GroupRelationship::Members->value)
                ->withCount([GroupRelationship::Members->value, GroupRelationship::Projects->value])
                ->orderBy(GroupName::getDatabaseTableColumnName())
                ->get()
                ->map(fn (GroupEloquentModel $group): array => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'description' => $group->description,
                    'hue' => $group->hue,
                    'icon' => $group->icon,
                    'members_count' => Types::intOrZero($group->getAttribute('members_count')),
                    'projects_count' => Types::intOrZero($group->getAttribute('projects_count')),
                    'members' => $group->members
                        ->map(fn (UserEloquentModel $member): array => ['id' => $member->id, 'name' => $member->name])
                        ->values()
                        ->all(),
                ])
                ->values(),
            'canInvite' => $canInvite,
            'canChangeRoles' => Gate::allows('update', $team),
            'roles' => array_map(fn (TeamRoleEnum $role): string => $role->value, TeamRoleEnum::cases()),
        ]);
    }
}
