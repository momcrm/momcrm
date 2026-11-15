<?php

declare(strict_types=1);

namespace App\Project;

use App\Activity\Activity;
use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityRelationship;
use App\Activity\Properties\SubjectId;
use App\Activity\Properties\SubjectType;
use App\Milestone\MilestoneEloquentModel;
use App\Milestone\Properties\DueOn as MilestoneDueOn;
use App\Milestone\Properties\ProjectId as MilestoneProjectId;
use App\Shared\MorphMapKeyEnum;
use App\Shared\Table;
use App\Shared\Types;
use App\Team\Scopes\CurrentTeamScope;
use App\Ticket\Controllers\ListTicketsController;
use App\Ticket\Properties\ArchivedAt;
use App\Ticket\Properties\DueOn;
use App\Ticket\Properties\ParentId;
use App\Ticket\Properties\ProjectId;
use App\Ticket\Properties\Status;
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketRelationship;
use App\Ticket\TicketStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * What a project's Overview tab shows: tickets per status, what's due next,
 * the milestones and the latest activity on the project and its tickets.
 */
final class ProjectOverview
{
    /**
     * @return array{statusCounts: array<string, int>, upcoming: array<int, array<string, mixed>>, milestones: array<int, array{id: string, name: string, due_on: string, completed_at: ?string}>, activity: array<int, array<string, mixed>>}
     */
    public static function for(string $projectId): array
    {
        $tickets = fn (): Builder => TicketEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(ProjectId::getDatabaseTableColumnName(), $projectId)
            ->whereNull(ArchivedAt::getDatabaseTableColumnName())
            ->whereNull(ParentId::getDatabaseTableColumnName());

        $statusCounts = $tickets()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy(Status::getDatabaseTableColumnName())
            ->get()
            ->mapWithKeys(fn (TicketEloquentModel $row): array => [$row->status => Types::int($row->getAttribute('aggregate'))])
            ->all();

        return [
            'statusCounts' => $statusCounts,
            'upcoming' => $tickets()
                ->with([TicketRelationship::Assignee->value, TicketRelationship::Labels->value])
                ->whereIn(Status::getDatabaseTableColumnName(), TicketStatusEnum::openValues())
                ->whereNotNull(DueOn::getDatabaseTableColumnName())
                ->orderBy(DueOn::getDatabaseTableColumnName())
                ->limit(6)
                ->get()
                ->map(fn (TicketEloquentModel $model): array => ListTicketsController::listItem(Ticket::fromEloquentModel($model)))
                ->values()
                ->all(),
            'milestones' => MilestoneEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->where(MilestoneProjectId::getDatabaseTableColumnName(), $projectId)
                ->orderBy(MilestoneDueOn::getDatabaseTableColumnName())
                ->get()
                ->map(fn (MilestoneEloquentModel $milestone): array => [
                    'id' => $milestone->id,
                    'name' => $milestone->name,
                    'due_on' => substr($milestone->due_on, 0, 10),
                    'completed_at' => $milestone->completed_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'activity' => ActivityEloquentModel::query()
                ->with(ActivityRelationship::Actor->value)
                ->where(fn (Builder $query) => $query
                    ->where(fn (Builder $query) => $query
                        ->where(SubjectType::getDatabaseTableColumnName(), MorphMapKeyEnum::Project->value)
                        ->where(SubjectId::getDatabaseTableColumnName(), $projectId))
                    ->orWhere(fn (Builder $query) => $query
                        ->where(SubjectType::getDatabaseTableColumnName(), MorphMapKeyEnum::Ticket->value)
                        ->whereIn(SubjectId::getDatabaseTableColumnName(), fn (QueryBuilder $tickets) => $tickets
                            ->select('id')
                            ->from(Table::Tickets->value)
                            ->where(ProjectId::getDatabaseTableColumnName(), $projectId))))
                ->latest()
                ->limit(12)
                ->get()
                ->map(fn (ActivityEloquentModel $model): array => Activity::fromEloquentModel($model)->toListItem())
                ->values()
                ->all(),
        ];
    }
}
