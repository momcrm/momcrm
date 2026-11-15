<?php

declare(strict_types=1);

namespace App\Dashboard;

use App\Activity\Activity;
use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityRelationship;
use App\Activity\ActivityTypeEnum;
use App\Activity\Properties\SubjectId;
use App\Activity\Properties\SubjectType;
use App\Activity\Properties\TeamId as ActivityTeamId;
use App\Activity\Properties\Type;
use App\Milestone\MilestoneEloquentModel;
use App\Milestone\MilestoneRelationship;
use App\Milestone\Properties\CompletedAt;
use App\Milestone\Properties\DueOn as MilestoneDueOn;
use App\Milestone\Properties\TeamId as MilestoneTeamId;
use App\Project\ProjectEloquentModel;
use App\Project\ProjectStatusEnum;
use App\Project\ProjectVisibility;
use App\Project\Properties\ArchivedAt as ProjectArchivedAt;
use App\Project\Properties\Status as ProjectStatus;
use App\Project\Properties\TeamId as ProjectTeamId;
use App\Shared\MorphMapKeyEnum;
use App\Shared\Types;
use App\Team\Scopes\CurrentTeamScope;
use App\TeamUser\Actions\FindTeamMembers;
use App\Ticket\Controllers\ListTicketsController;
use App\Ticket\Properties\ArchivedAt;
use App\Ticket\Properties\AssigneeId;
use App\Ticket\Properties\ClosedAt;
use App\Ticket\Properties\DueOn;
use App\Ticket\Properties\Number;
use App\Ticket\Properties\ParentId;
use App\Ticket\Properties\StatusId;
use App\Ticket\Properties\TeamId;
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketRelationship;
use App\Ticket\TicketStatusCategoryEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * The team-wide figures Home and Overview show. Every count is of live
 * (not archived) tickets that are not subtasks.
 */
final class WorkspaceNumbers
{
    /**
     * @return array{active_projects: int, at_risk_projects: int, open_tickets: int, closed_this_week: int, overdue_tickets: int}
     */
    public static function headline(string $teamId): array
    {
        $today = CarbonImmutable::today();

        return [
            'active_projects' => self::liveProjects($teamId)->where(ProjectStatus::getDatabaseTableColumnName(), '!=', ProjectStatusEnum::Completed->value)->count(),
            'at_risk_projects' => self::liveProjects($teamId)->where(ProjectStatus::getDatabaseTableColumnName(), ProjectStatusEnum::AtRisk->value)->count(),
            'open_tickets' => self::open($teamId)->count(),
            'closed_this_week' => self::tickets($teamId)
                ->whereIn(StatusId::getDatabaseTableColumnName(), TicketStatusCategoryEnum::Closed->statusIds())
                ->where(ClosedAt::getDatabaseTableColumnName(), '>=', $today->startOfWeek(CarbonImmutable::MONDAY))
                ->count(),
            'overdue_tickets' => self::open($teamId)->where(DueOn::getDatabaseTableColumnName(), '<', $today->toDateString())->count(),
        ];
    }

    /**
     * Open tickets due from today to a week out, soonest first.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function deadlines(string $teamId): array
    {
        $today = CarbonImmutable::today();

        return self::open($teamId)
            ->with([TicketRelationship::Project->value, TicketRelationship::Assignee->value, TicketRelationship::Customer->value, TicketRelationship::Labels->value])
            ->whereBetween(DueOn::getDatabaseTableColumnName(), [$today->toDateString(), $today->addDays(7)->toDateString()])
            ->orderBy(DueOn::getDatabaseTableColumnName())
            ->orderBy(Number::getDatabaseTableColumnName())
            ->limit(20)
            ->get()
            ->map(fn (TicketEloquentModel $model): array => ListTicketsController::listItem(Ticket::fromEloquentModel($model)))
            ->values()
            ->all();
    }

    /**
     * How many tickets the team has in each status, keyed by status id.
     *
     * @return array<string, int>
     */
    public static function statusCounts(string $teamId): array
    {
        return self::tickets($teamId)
            ->selectRaw('status_id, count(*) as aggregate')
            ->groupBy(StatusId::getDatabaseTableColumnName())
            ->get()
            ->mapWithKeys(fn (TicketEloquentModel $row): array => [(string) $row->status_id => Types::int($row->getAttribute('aggregate'))])
            ->all();
    }

    /**
     * Each member's open tickets, by status id, busiest first.
     *
     * @return array<int, array{id: string, name: string, total: int, statuses: array<string, int>}>
     */
    public static function workload(string $teamId): array
    {
        $counts = self::open($teamId)
            ->whereNotNull(AssigneeId::getDatabaseTableColumnName())
            ->selectRaw('assignee_id, status_id, count(*) as aggregate')
            ->groupBy(AssigneeId::getDatabaseTableColumnName(), StatusId::getDatabaseTableColumnName())
            ->get();

        return FindTeamMembers::optionsForTeam($teamId)
            ->map(function (array $member) use ($counts): array {
                $statuses = $counts
                    ->where(AssigneeId::getDatabaseTableColumnName(), $member['id'])
                    ->mapWithKeys(fn (TicketEloquentModel $row): array => [(string) $row->status_id => Types::int($row->getAttribute('aggregate'))])
                    ->all();

                return ['id' => $member['id'], 'name' => $member['name'], 'total' => array_sum($statuses), 'statuses' => $statuses];
            })
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /**
     * Unfinished milestones of live projects due in the next two months.
     *
     * @return array<int, array{id: string, name: string, due_on: string, project_id: string, project_name: ?string, project_hue: ?int}>
     */
    public static function upcomingMilestones(string $teamId): array
    {
        return MilestoneEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->with(MilestoneRelationship::Project->value)
            ->where(MilestoneTeamId::getDatabaseTableColumnName(), $teamId)
            ->whereNull(CompletedAt::getDatabaseTableColumnName())
            ->whereBetween(MilestoneDueOn::getDatabaseTableColumnName(), [CarbonImmutable::today()->subDays(30)->toDateString(), CarbonImmutable::today()->addDays(60)->toDateString()])
            ->whereHas(MilestoneRelationship::Project->value, fn (Builder $query) => $query->whereNull(ProjectArchivedAt::getDatabaseTableColumnName()))
            ->orderBy(MilestoneDueOn::getDatabaseTableColumnName())
            ->limit(10)
            ->get()
            ->map(fn (MilestoneEloquentModel $milestone): array => [
                'id' => $milestone->id,
                'name' => $milestone->name,
                'due_on' => substr($milestone->due_on, 0, 10),
                'project_id' => $milestone->project_id,
                'project_name' => $milestone->project?->name,
                'project_hue' => $milestone->project?->hue,
            ])
            ->values()
            ->all();
    }

    /**
     * The team's latest work on tickets and projects someone may see.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function recentActivity(string $teamId, string $userId, int $limit = 8): array
    {
        return ActivityEloquentModel::query()
            ->with(ActivityRelationship::Actor->value)
            ->where(ActivityTeamId::getDatabaseTableColumnName(), $teamId)
            ->whereIn(Type::getDatabaseTableColumnName(), array_map(fn (ActivityTypeEnum $type): string => $type->value, ActivityTypeEnum::work()))
            ->tap(fn (Builder $query) => ProjectVisibility::constrainActivities($query->getQuery(), $userId))
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (ActivityEloquentModel $model): array => [
                ...Activity::fromEloquentModel($model)->toListItem(),
                'url' => match ($model->getAttribute(SubjectType::getDatabaseTableColumnName())) {
                    MorphMapKeyEnum::Ticket->value => route('tickets.index', ['ticket' => Types::string($model->getAttribute(SubjectId::getDatabaseTableColumnName()))]),
                    MorphMapKeyEnum::Project->value => route('projects.show', Types::string($model->getAttribute(SubjectId::getDatabaseTableColumnName()))),
                    default => null,
                },
            ])
            ->values()
            ->all();
    }

    /**
     * @return Builder<TicketEloquentModel>
     */
    private static function tickets(string $teamId): Builder
    {
        return TicketEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->whereNull(ArchivedAt::getDatabaseTableColumnName())
            ->whereNull(ParentId::getDatabaseTableColumnName());
    }

    /**
     * @return Builder<TicketEloquentModel>
     */
    private static function open(string $teamId): Builder
    {
        return self::tickets($teamId)->whereIn(StatusId::getDatabaseTableColumnName(), TicketStatusCategoryEnum::Open->statusIds());
    }

    /**
     * @return Builder<ProjectEloquentModel>
     */
    private static function liveProjects(string $teamId): Builder
    {
        return ProjectEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(ProjectTeamId::getDatabaseTableColumnName(), $teamId)
            ->whereNull(ProjectArchivedAt::getDatabaseTableColumnName());
    }
}
