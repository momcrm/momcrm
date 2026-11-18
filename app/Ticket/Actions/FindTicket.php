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
use App\Ticket\Properties\ProjectId;
use App\Ticket\Properties\StatusId;
use App\Ticket\Properties\TeamId;
use App\Ticket\Properties\Title;
use App\Ticket\Ticket;
use App\Ticket\TicketCollection;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketListFilters;
use App\Ticket\TicketRelationship;
use App\Ticket\TicketStatusCategoryEnum;
use Illuminate\Database\Eloquent\Builder;
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
     * The team's ticket with this number, as people quote it: "#123".
     */
    public static function byNumberForTeam(int $number, string $teamId): Ticket
    {
        return Ticket::fromEloquentModel(self::query($teamId)->where(Number::getDatabaseTableColumnName(), $number)->first());
    }

    /**
     * Live (not archived) tickets newest first, optionally only open or closed
     * ones, matching a search on the title or the ticket number.
     *
     * @return LengthAwarePaginator<int, Ticket>
     */
    public static function paginateForTeam(string $teamId, string $filter = self::FILTER_OPEN, ?string $search = null, ?string $customerId = null, int $perPage = 25, ?string $assigneeId = null, ?string $projectId = null): LengthAwarePaginator
    {
        return self::query($teamId)
            ->with([TicketRelationship::Customer->value, TicketRelationship::Assignee->value, TicketRelationship::Project->value])
            ->whereNull(ArchivedAt::getDatabaseTableColumnName())
            ->when($projectId, fn (Builder $query, string $projectId) => $query->where(ProjectId::getDatabaseTableColumnName(), $projectId))
            ->when($filter === self::FILTER_OPEN, fn (Builder $query) => $query->whereIn(StatusId::getDatabaseTableColumnName(), TicketStatusCategoryEnum::Open->statusIds()))
            ->when($filter === self::FILTER_CLOSED, fn (Builder $query) => $query->whereIn(StatusId::getDatabaseTableColumnName(), TicketStatusCategoryEnum::Closed->statusIds()))
            ->when($customerId, fn (Builder $query, string $customerId) => $query->where(CustomerId::getDatabaseTableColumnName(), $customerId))
            ->when($assigneeId, fn (Builder $query, string $assigneeId) => $query->where(AssigneeId::getDatabaseTableColumnName(), $assigneeId))
            ->when($search, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query
                ->where(Title::getDatabaseTableColumnName(), 'like', "%{$search}%")
                ->orWhere(Number::getDatabaseTableColumnName(), ltrim($search, '#'))))
            ->orderByDesc(Number::getDatabaseTableColumnName())
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (TicketEloquentModel $model): Ticket => Ticket::fromEloquentModel($model));
    }

    /**
     * Live tickets for the ticket views, filtered and sorted as the toolbar
     * says. The board orders each column by its position instead.
     *
     * @return LengthAwarePaginator<int, Ticket>
     */
    public static function paginateFiltered(string $teamId, string $userId, TicketListFilters $filters, int $perPage): LengthAwarePaginator
    {
        $query = self::query($teamId)
            ->with([
                TicketRelationship::Customer->value,
                TicketRelationship::Assignee->value,
                TicketRelationship::Project->value,
                TicketRelationship::Labels->value,
            ])
            ->withCount([
                TicketRelationship::Subtasks->value,
                TicketRelationship::Subtasks->value.' as closed_subtasks_count' => fn (Builder $query) => $query->whereIn(StatusId::getDatabaseTableColumnName(), TicketStatusCategoryEnum::Closed->statusIds()),
            ])
            ->whereNull(ArchivedAt::getDatabaseTableColumnName())
            ->whereNull(ParentId::getDatabaseTableColumnName());

        $filters->narrow($query, $userId);

        if ($filters->view === TicketListFilters::VIEW_BOARD) {
            $query->orderBy(Position::getDatabaseTableColumnName())->orderByDesc(Number::getDatabaseTableColumnName());
        } else {
            $filters->sort($query);
        }

        return $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (TicketEloquentModel $model): Ticket => Ticket::fromEloquentModel($model));
    }

    /**
     * Live tickets assigned to someone, with a due date or still open, for My Tickets.
     */
    public static function assignedTo(string $teamId, string $userId): TicketCollection
    {
        return Ticket::collect(self::query($teamId)
            ->with([TicketRelationship::Customer->value, TicketRelationship::Assignee->value, TicketRelationship::Project->value, TicketRelationship::Labels->value])
            ->whereNull(ArchivedAt::getDatabaseTableColumnName())
            ->where(AssigneeId::getDatabaseTableColumnName(), $userId)
            ->where(fn (Builder $query) => $query
                ->whereIn(StatusId::getDatabaseTableColumnName(), TicketStatusCategoryEnum::Open->statusIds())
                ->orWhere(ClosedAt::getDatabaseTableColumnName(), '>=', now()->subDays(14)))
            ->orderByRaw('due_on is null, due_on')
            ->orderByDesc(Number::getDatabaseTableColumnName())
            ->limit(200)
            ->get()
            ->all());
    }

    /**
     * Some tickets by id, live or archived, with what list rows show.
     *
     * @param  array<int, string>  $ids
     */
    public static function byIdsForTeam(string $teamId, array $ids, bool $archived = false): TicketCollection
    {
        return Ticket::collect(self::query($teamId)
            ->with([TicketRelationship::Customer->value, TicketRelationship::Assignee->value, TicketRelationship::Project->value, TicketRelationship::Labels->value])
            ->whereIn(Id::getDatabaseTableColumnName(), $ids)
            ->when($archived, fn (Builder $query) => $query->whereNotNull(ArchivedAt::getDatabaseTableColumnName()), fn (Builder $query) => $query->whereNull(ArchivedAt::getDatabaseTableColumnName()))
            ->orderByDesc(Number::getDatabaseTableColumnName())
            ->get()
            ->all());
    }

    /**
     * The team's archived tickets, most recently archived first.
     */
    public static function archivedForTeam(string $teamId): TicketCollection
    {
        return Ticket::collect(self::query($teamId)
            ->with([TicketRelationship::Customer->value, TicketRelationship::Assignee->value, TicketRelationship::Project->value, TicketRelationship::Labels->value])
            ->whereNotNull(ArchivedAt::getDatabaseTableColumnName())
            ->orderByDesc(ArchivedAt::getDatabaseTableColumnName())
            ->limit(200)
            ->get()
            ->all());
    }

    /**
     * How many open tickets (not subtasks) the team has, optionally only those
     * assigned to one person.
     */
    public static function countOpenForTeam(string $teamId, ?string $assigneeId = null): int
    {
        return self::query($teamId)
            ->whereNull(ArchivedAt::getDatabaseTableColumnName())
            ->whereNull(ParentId::getDatabaseTableColumnName())
            ->whereIn(StatusId::getDatabaseTableColumnName(), TicketStatusCategoryEnum::Open->statusIds())
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
