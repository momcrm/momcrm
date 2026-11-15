<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Milestone\MilestoneEloquentModel;
use App\Milestone\Properties\DueOn as MilestoneDueOn;
use App\Milestone\Properties\ProjectId as MilestoneProjectId;
use App\Project\Actions\FindProject;
use App\Project\Project;
use App\Team\Scopes\CurrentTeamScope;
use App\Ticket\Controllers\ListTicketsController;
use App\Ticket\Properties\ArchivedAt;
use App\Ticket\Properties\DueOn;
use App\Ticket\Properties\Number;
use App\Ticket\Properties\ParentId;
use App\Ticket\Properties\ProjectId;
use App\Ticket\Properties\StartsOn;
use App\Ticket\Properties\TeamId;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * What the timeline shows: a window of weeks or months with each dated ticket
 * as a bar from its start to its due date (a single day when it has no start),
 * grouped by project, plus each project's own span and milestones.
 */
final class TicketTimeline
{
    public const string WEEKS = 'weeks';

    public const string MONTHS = 'months';

    /**
     * @return array{timeline: array{zoom: string, from: string, to: string}, tickets: array<int, array<string, mixed>>, projects: array<int, array{id: string, name: string, color: string, icon: ?string, starts_on: ?string, due_on: ?string}>, milestones: array<int, array{id: string, project_id: string, name: string, due_on: string, completed_at: ?string}>}
     */
    public static function forRequest(Request $request, ?string $projectId = null): array
    {
        $zoom = $request->query('zoom') === self::MONTHS ? self::MONTHS : self::WEEKS;
        $start = TicketCalendar::date($request->query('start'));
        [$from, $to] = $zoom === self::MONTHS
            ? [$start->startOfMonth(), $start->startOfMonth()->addMonths(6)->subDay()]
            : [$start->startOfWeek(CarbonImmutable::MONDAY), $start->startOfWeek(CarbonImmutable::MONDAY)->addWeeks(8)->subDay()];

        /** @var UserEloquentModel $user */
        $user = $request->user();
        $teamId = (string) $user->current_team_id;
        $window = [$from->toDateString(), $to->toDateString()];

        $projects = $projectId === null
            ? FindProject::listForTeam($teamId, $user->id)
            : FindProject::liveByIds($teamId, $user->id, [$projectId]);
        $projectIds = $projects->map(fn (Project $project): string => $project->getId()->toString())->all();

        return [
            'timeline' => ['zoom' => $zoom, 'from' => $window[0], 'to' => $window[1]],
            'tickets' => TicketEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->with([TicketRelationship::Project->value, TicketRelationship::Assignee->value, TicketRelationship::Customer->value, TicketRelationship::Labels->value])
                ->where(TeamId::getDatabaseTableColumnName(), $teamId)
                ->whereNull(ArchivedAt::getDatabaseTableColumnName())
                ->whereNull(ParentId::getDatabaseTableColumnName())
                ->when(
                    $projectId,
                    fn (Builder $query, string $projectId) => $query->where(ProjectId::getDatabaseTableColumnName(), $projectId),
                    fn (Builder $query) => $query->where(fn (Builder $query) => $query
                        ->whereNull(ProjectId::getDatabaseTableColumnName())
                        ->orWhereIn(ProjectId::getDatabaseTableColumnName(), $projectIds)),
                )
                ->where(fn (Builder $query) => $query->whereNotNull(DueOn::getDatabaseTableColumnName())->orWhereNotNull(StartsOn::getDatabaseTableColumnName()))
                ->whereRaw('coalesce(starts_on, due_on) <= ?', [$window[1]])
                ->whereRaw('coalesce(due_on, starts_on) >= ?', [$window[0]])
                ->orderByRaw('coalesce(starts_on, due_on)')
                ->orderBy(Number::getDatabaseTableColumnName())
                ->limit(1000)
                ->get()
                ->map(fn (TicketEloquentModel $model): array => ListTicketsController::listItem(Ticket::fromEloquentModel($model)))
                ->values()
                ->all(),
            'projects' => $projects
                ->map(fn (Project $project): array => [
                    'id' => $project->getId()->toString(),
                    'name' => $project->getName()->toString(),
                    'color' => $project->getColor()->toString(),
                    'icon' => $project->getIcon()->toNullableString(),
                    'starts_on' => $project->getStartsOn()->toNullableCarbon()?->toDateString(),
                    'due_on' => $project->getDueOn()->toNullableCarbon()?->toDateString(),
                ])
                ->values()
                ->all(),
            'milestones' => MilestoneEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->whereIn(MilestoneProjectId::getDatabaseTableColumnName(), $projectIds)
                ->whereBetween(MilestoneDueOn::getDatabaseTableColumnName(), $window)
                ->orderBy(MilestoneDueOn::getDatabaseTableColumnName())
                ->get()
                ->map(fn (MilestoneEloquentModel $milestone): array => [
                    'id' => $milestone->id,
                    'project_id' => $milestone->project_id,
                    'name' => $milestone->name,
                    'due_on' => substr($milestone->due_on, 0, 10),
                    'completed_at' => $milestone->completed_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];
    }
}
