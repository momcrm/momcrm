<?php

declare(strict_types=1);

namespace App\TeamUser\Controllers;

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Activity\Properties\CreatedAt as ActivityCreatedAt;
use App\Activity\Properties\TeamId as ActivityTeamId;
use App\Activity\Properties\Type as ActivityType;
use App\Activity\Properties\UserId as ActivityUserId;
use App\CustomField\Actions\FindLinkedRecords;
use App\CustomField\CustomFieldRelationEnum;
use App\Dashboard\WorkspaceNumbers;
use App\Favorite\Actions\FindFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Group\GroupEloquentModel;
use App\Group\GroupRelationship;
use App\Group\Properties\Name as GroupName;
use App\Group\Properties\TeamId as GroupTeamId;
use App\Project\Actions\FindProject;
use App\Project\Project;
use App\Project\ProjectStatusEnum;
use App\Shared\Types;
use App\Team\Actions\FindTeam;
use App\Team\Scopes\CurrentTeamScope;
use App\TeamUser\Actions\FindTeamUser;
use App\TeamUser\TeamRoleEnum;
use App\Ticket\Controllers\ListTicketsController;
use App\Ticket\Properties\ArchivedAt;
use App\Ticket\Properties\AssigneeId;
use App\Ticket\Properties\ClosedAt;
use App\Ticket\Properties\DueOn;
use App\Ticket\Properties\ParentId;
use App\Ticket\Properties\StatusId;
use App\Ticket\Properties\TeamId as TicketTeamId;
use App\Ticket\Ticket;
use App\Ticket\TicketDrawer;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketRelationship;
use App\Ticket\TicketStatusCategoryEnum;
use App\User\User;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Someone's page in the team: who they are, the tickets assigned to them,
 * the projects they work on and what they have been doing lately. They were
 * last active at their latest work in this team or their latest sign-in. Only
 * tickets and projects the viewer may see are counted or listed.
 */
final class ShowMemberController
{
    private const int TICKETS_SHOWN = 50;

    public function __invoke(Request $request, string $member): Response
    {
        /** @var UserEloquentModel $viewer */
        $viewer = $request->user();
        $teamId = (string) $viewer->current_team_id;
        $team = FindTeam::byIdForMember($teamId, $viewer->id);
        $membership = FindTeamUser::byTeamAndUser($teamId, $member);
        $user = User::fromEloquentModel(UserEloquentModel::query()->find($member));

        if ($team->isNull() || $membership->isNull() || $user->isNull()) {
            throw new NotFoundHttpException('Member not found.');
        }

        $role = $membership->getRole()->toNullableEnum() ?? TeamRoleEnum::Member;
        $viewerRole = FindTeamUser::byTeamAndUser($teamId, $viewer->id)->getRole()->toNullableEnum();

        $assigned = fn (): Builder => TicketEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TicketTeamId::getDatabaseTableColumnName(), $teamId)
            ->where(AssigneeId::getDatabaseTableColumnName(), $member)
            ->whereNull(ArchivedAt::getDatabaseTableColumnName())
            ->whereNull(ParentId::getDatabaseTableColumnName());
        $open = fn (): Builder => $assigned()->whereIn(StatusId::getDatabaseTableColumnName(), TicketStatusCategoryEnum::Open->statusIds());
        $closed = fn (): Builder => $assigned()->whereIn(StatusId::getDatabaseTableColumnName(), TicketStatusCategoryEnum::Closed->statusIds());
        $withListDetails = [TicketRelationship::Project->value, TicketRelationship::Assignee->value, TicketRelationship::Customer->value, TicketRelationship::Labels->value];

        $projects = FindProject::listForTeam($teamId, $viewer->id)
            ->filter(fn (Project $project): bool => ! $project->getStatus()->equalsTo(ProjectStatusEnum::Completed->value)
                && ($project->getLeadId()->equalsTo($member) || $project->getMembers()->toDataCollection()->contains(fn (User $projectMember): bool => $projectMember->getId()->equalsTo($member))))
            ->values();

        $lastActiveAt = ActivityEloquentModel::query()
            ->where(ActivityUserId::getDatabaseTableColumnName(), $member)
            ->where(fn (Builder $query) => $query
                ->where(ActivityTeamId::getDatabaseTableColumnName(), $teamId)
                ->orWhere(ActivityType::getDatabaseTableColumnName(), ActivityTypeEnum::SignedIn->value))
            ->max(ActivityCreatedAt::getDatabaseTableColumnName());

        return Inertia::render('members/show', [
            'teamName' => $team->getName()->toString(),
            'linkedRecords' => FindLinkedRecords::to($teamId, $viewer->id, CustomFieldRelationEnum::Member, $member),
            'member' => [
                'id' => $user->getId()->toString(),
                'name' => $user->getName()->toString(),
                'email' => $user->getEmail()->toString(),
                'job_title' => $user->getJobTitle()->toNullableString(),
                'timezone' => $user->getTimezone()->toNullableString(),
                'role' => $role->value,
                'is_you' => $user->getId()->equalsTo($viewer->id),
                'can_remove' => ! $user->getId()->equalsTo($viewer->id) && $viewerRole?->canRemove($role) === true,
                'joined_at' => $membership->getCreatedAt()->toNullableISOString(),
                'last_active_at' => $lastActiveAt === null ? null : now()->parse(Types::string($lastActiveAt))->toIso8601String(),
                'groups' => GroupEloquentModel::query()
                    ->withoutGlobalScopes()
                    ->where(GroupTeamId::getDatabaseTableColumnName(), $teamId)
                    ->whereHas(GroupRelationship::Members->value, fn (Builder $query) => $query->whereKey($member))
                    ->orderBy(GroupName::getDatabaseTableColumnName())
                    ->get()
                    ->map(fn (GroupEloquentModel $group): array => ['id' => $group->id, 'name' => $group->name, 'hue' => $group->hue])
                    ->values()
                    ->all(),
            ],
            'stats' => [
                'active_projects' => $projects->count(),
                'open_tickets' => $open()->count(),
                'closed_tickets' => $closed()->count(),
                'overdue_tickets' => $open()->where(DueOn::getDatabaseTableColumnName(), '<', now()->toDateString())->count(),
            ],
            'openTickets' => $open()
                ->with($withListDetails)
                ->orderByRaw('due_on is null, due_on')
                ->limit(self::TICKETS_SHOWN)
                ->get()
                ->map(fn (TicketEloquentModel $model): array => ListTicketsController::listItem(Ticket::fromEloquentModel($model)))
                ->values(),
            'closedTickets' => $closed()
                ->with($withListDetails)
                ->orderByDesc(ClosedAt::getDatabaseTableColumnName())
                ->limit(self::TICKETS_SHOWN)
                ->get()
                ->map(fn (TicketEloquentModel $model): array => ListTicketsController::listItem(Ticket::fromEloquentModel($model)))
                ->values(),
            'projects' => $projects
                ->map(fn (Project $project): array => [
                    ...$project->toSummary(),
                    'is_lead' => $project->getLeadId()->equalsTo($member),
                ])
                ->values(),
            'activity' => WorkspaceNumbers::recentActivity($teamId, $viewer->id, actorId: $member),
            'canChangeRoles' => Gate::allows('update', $team) && $role !== TeamRoleEnum::Owner && ! $user->getId()->equalsTo($viewer->id),
            'canInvite' => Gate::allows('invite', $team),
            'favoriteTicketIds' => FindFavorite::idsForUser($teamId, $viewer->id, FavoritableTypeEnum::Ticket),
            ...TicketDrawer::options($teamId, $viewer->id),
            'drawer' => TicketDrawer::forRequest($request),
        ]);
    }
}
