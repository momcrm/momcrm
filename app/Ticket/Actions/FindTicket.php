<?php

declare(strict_types=1);

namespace App\Ticket\Actions;

use App\Team\Scopes\CurrentTeamScope;
use App\Ticket\Properties\ArchivedAt;
use App\Ticket\Properties\AssigneeId;
use App\Ticket\Properties\ClosedAt;
use App\Ticket\Properties\CustomerId;
use App\Ticket\Properties\Id;
use App\Ticket\Properties\Number;
use App\Ticket\Properties\ParentId;
use App\Ticket\Properties\Position;
use App\Ticket\Properties\Priority;
use App\Ticket\Properties\ProjectId;
use App\Ticket\Properties\Status;
use App\Ticket\Properties\TeamId;
use App\Ticket\Properties\Title;
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketListFilters;
use App\Ticket\TicketRelationship;
use App\Ticket\TicketStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class FindTicket
{
    public const string FILTER_OPEN = 'open';

    public const string FILTER_CLOSED = 'closed';

    public const string FILTER_ALL = 'all';

    public static function byIdForTeam(?string $id, ?string $teamId): Ticket
    {
        if ($id === null || $teamId === null) {
            return Ticket::new();
        }

        return Ticket::fromEloquentModel(self::query($teamId)->where(Id::getDatabaseTableColumnName(), $id)->first());
    }

    /**
     * Live (not archived) tickets newest first, optionally only open or closed
     * ones, matching a search on the title or the ticket number.
     *
     * @return LengthAwarePaginator<int, TicketEloquentModel>
     */
    public static function paginateForTeam(string $teamId, string $filter = self::FILTER_OPEN, ?string $search = null, ?string $customerId = null, int $perPage = 25, ?string $assigneeId = null, ?string $projectId = null): LengthAwarePaginator
    {
        return self::query($teamId)
            ->with([TicketRelationship::Customer->value, TicketRelationship::Assignee->value, TicketRelationship::Project->value])
            ->whereNull(ArchivedAt::getDatabaseTableColumnName())
            ->when($projectId, fn (Builder $query, string $projectId) => $query->where(ProjectId::getDatabaseTableColumnName(), $projectId))
            ->when($filter === self::FILTER_OPEN, fn (Builder $query) => $query->whereIn(Status::getDatabaseTableColumnName(), TicketStatusEnum::openValues()))
            ->when($filter === self::FILTER_CLOSED, fn (Builder $query) => $query->where(Status::getDatabaseTableColumnName(), TicketStatusEnum::Closed->value))
            ->when($customerId, fn (Builder $query, string $customerId) => $query->where(CustomerId::getDatabaseTableColumnName(), $customerId))
            ->when($assigneeId, fn (Builder $query, string $assigneeId) => $query->where(AssigneeId::getDatabaseTableColumnName(), $assigneeId))
            ->when($search, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query
                ->where(Title::getDatabaseTableColumnName(), 'like', "%{$search}%")
                ->orWhere(Number::getDatabaseTableColumnName(), ltrim($search, '#'))))
            ->orderByDesc(Number::getDatabaseTableColumnName())
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Live tickets for the ticket views, filtered and sorted as the toolbar
     * says. The board orders each column by its position instead.
     *
     * @return LengthAwarePaginator<int, TicketEloquentModel>
     */
    public static function paginateFiltered(string $teamId, string $userId, TicketListFilters $filters, int $perPage): LengthAwarePaginator
    {
        $isAscending = $filters->direction === 'asc';

        return self::query($teamId)
            ->with([
                TicketRelationship::Customer->value,
                TicketRelationship::Assignee->value,
                TicketRelationship::Project->value,
                TicketRelationship::Labels->value,
            ])
            ->withCount([
                TicketRelationship::Subtasks->value,
                TicketRelationship::Subtasks->value.' as closed_subtasks_count' => fn (Builder $query) => $query->where(Status::getDatabaseTableColumnName(), TicketStatusEnum::Closed->value),
            ])
            ->whereNull(ArchivedAt::getDatabaseTableColumnName())
            ->whereNull(ParentId::getDatabaseTableColumnName())
            ->when($filters->status === self::FILTER_OPEN, fn (Builder $query) => $query->whereIn(Status::getDatabaseTableColumnName(), TicketStatusEnum::openValues()))
            ->when($filters->status === self::FILTER_CLOSED, fn (Builder $query) => $query->where(Status::getDatabaseTableColumnName(), TicketStatusEnum::Closed->value))
            ->when($filters->priorities !== [], fn (Builder $query) => $query->whereIn(Priority::getDatabaseTableColumnName(), $filters->priorities))
            ->when($filters->assignee, fn (Builder $query, string $assignee) => match ($assignee) {
                TicketListFilters::ASSIGNEE_ME => $query->where(AssigneeId::getDatabaseTableColumnName(), $userId),
                TicketListFilters::NONE => $query->whereNull(AssigneeId::getDatabaseTableColumnName()),
                default => $query->where(AssigneeId::getDatabaseTableColumnName(), $assignee),
            })
            ->when($filters->project, fn (Builder $query, string $project) => $project === TicketListFilters::NONE
                ? $query->whereNull(ProjectId::getDatabaseTableColumnName())
                : $query->where(ProjectId::getDatabaseTableColumnName(), $project))
            ->when($filters->label, fn (Builder $query, string $label) => $query->whereHas(
                TicketRelationship::Labels->value,
                fn (Builder $query) => $query->whereKey($label),
            ))
            ->when($filters->search, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query
                ->where(Title::getDatabaseTableColumnName(), 'like', "%{$search}%")
                ->orWhere(Number::getDatabaseTableColumnName(), ltrim($search, '#'))))
            ->when(
                $filters->view === TicketListFilters::VIEW_BOARD,
                fn (Builder $query) => $query->orderBy(Position::getDatabaseTableColumnName())->orderByDesc(Number::getDatabaseTableColumnName()),
                fn (Builder $query) => match ($filters->sort) {
                    'priority' => $query->orderByRaw($isAscending
                        ? "case priority when 'urgent' then 4 when 'high' then 3 when 'normal' then 2 else 1 end asc"
                        : "case priority when 'urgent' then 4 when 'high' then 3 when 'normal' then 2 else 1 end desc",
                    )->orderByDesc(Number::getDatabaseTableColumnName()),
                    'due_on' => $query->orderByRaw($isAscending ? 'due_on is null, due_on asc' : 'due_on is null, due_on desc')
                        ->orderByDesc(Number::getDatabaseTableColumnName()),
                    default => $query->orderBy(TicketListFilters::SORTS[$filters->sort], $filters->direction),
                },
            )
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Live tickets assigned to someone, with a due date or still open, for My Tickets.
     *
     * @return Collection<int, TicketEloquentModel>
     */
    public static function assignedTo(string $teamId, string $userId): Collection
    {
        return self::query($teamId)
            ->with([TicketRelationship::Customer->value, TicketRelationship::Assignee->value, TicketRelationship::Project->value, TicketRelationship::Labels->value])
            ->whereNull(ArchivedAt::getDatabaseTableColumnName())
            ->where(AssigneeId::getDatabaseTableColumnName(), $userId)
            ->where(fn (Builder $query) => $query
                ->whereIn(Status::getDatabaseTableColumnName(), TicketStatusEnum::openValues())
                ->orWhere(ClosedAt::getDatabaseTableColumnName(), '>=', now()->subDays(14)))
            ->orderByRaw('due_on is null, due_on')
            ->orderByDesc(Number::getDatabaseTableColumnName())
            ->limit(200)
            ->get();
    }

    /**
     * How many open tickets the team has, optionally only those assigned to one person.
     */
    public static function countOpenForTeam(string $teamId, ?string $assigneeId = null): int
    {
        return self::query($teamId)
            ->whereNull(ArchivedAt::getDatabaseTableColumnName())
            ->whereIn(Status::getDatabaseTableColumnName(), TicketStatusEnum::openValues())
            ->when($assigneeId, fn (Builder $query, string $assigneeId) => $query->where(AssigneeId::getDatabaseTableColumnName(), $assigneeId))
            ->count();
    }

    /**
     * @return Builder<TicketEloquentModel>
     */
    private static function query(string $teamId): Builder
    {
        return TicketEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId);
    }
}
