<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Project\Actions\FindProject;
use App\Team\Scopes\CurrentTeamScope;
use App\Ticket\Controllers\ListTicketsController;
use App\Ticket\Properties\ArchivedAt;
use App\Ticket\Properties\AssigneeId;
use App\Ticket\Properties\DueOn;
use App\Ticket\Properties\Number;
use App\Ticket\Properties\ParentId;
use App\Ticket\Properties\ProjectId;
use App\Ticket\Properties\TeamId;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * What the calendar shows: a month (Monday-first weeks that cover it) or a
 * week of tickets placed on their due dates, optionally only one project's or
 * only the signed-in person's.
 */
final class TicketCalendar
{
    public const string MONTH = 'month';

    public const string WEEK = 'week';

    /**
     * @return array{calendar: array{view: string, date: string, from: string, to: string, mine: bool}, tickets: array<int, array<string, mixed>>, projects: array<int, array{id: string, name: string, hue: ?int, customer_id: ?string}>}
     */
    public static function forRequest(Request $request, ?string $projectId = null): array
    {
        $view = $request->query('calendar') === self::WEEK ? self::WEEK : self::MONTH;
        $date = self::date($request->query('date'));
        $mine = $projectId === null && $request->boolean('mine');

        [$from, $to] = $view === self::WEEK
            ? [$date->startOfWeek(CarbonImmutable::MONDAY), $date->endOfWeek(CarbonImmutable::SUNDAY)]
            : [$date->startOfMonth()->startOfWeek(CarbonImmutable::MONDAY), $date->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY)];

        /** @var UserEloquentModel $user */
        $user = $request->user();
        $teamId = (string) $user->current_team_id;

        return [
            'calendar' => [
                'view' => $view,
                'date' => $date->toDateString(),
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'mine' => $mine,
            ],
            'tickets' => TicketEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->with([TicketRelationship::Project->value, TicketRelationship::Assignee->value, TicketRelationship::Customer->value, TicketRelationship::Labels->value])
                ->where(TeamId::getDatabaseTableColumnName(), $teamId)
                ->whereNull(ArchivedAt::getDatabaseTableColumnName())
                ->whereNull(ParentId::getDatabaseTableColumnName())
                ->whereBetween(DueOn::getDatabaseTableColumnName(), [$from->toDateString(), $to->toDateString()])
                ->when($projectId, fn (Builder $query, string $projectId) => $query->where(ProjectId::getDatabaseTableColumnName(), $projectId))
                ->when($mine, fn (Builder $query) => $query->where(AssigneeId::getDatabaseTableColumnName(), $user->id))
                ->orderBy(DueOn::getDatabaseTableColumnName())
                ->orderBy(Number::getDatabaseTableColumnName())
                ->limit(1000)
                ->get()
                ->map(fn (TicketEloquentModel $model): array => ListTicketsController::listItem(Ticket::fromEloquentModel($model)))
                ->values()
                ->all(),
            'projects' => FindProject::optionsForTeam($teamId, $user->id)->values()->all(),
        ];
    }

    /**
     * A YYYY-MM-DD date from the query string, or today.
     */
    public static function date(mixed $value): CarbonImmutable
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            $date = CarbonImmutable::createFromFormat('Y-m-d', $value);

            if ($date instanceof CarbonImmutable) {
                return $date->startOfDay();
            }
        }

        return CarbonImmutable::today();
    }
}
