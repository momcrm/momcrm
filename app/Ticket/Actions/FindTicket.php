<?php

declare(strict_types=1);

namespace App\Ticket\Actions;

use App\Team\Scopes\CurrentTeamScope;
use App\Ticket\Properties\AssigneeId;
use App\Ticket\Properties\CustomerId;
use App\Ticket\Properties\Id;
use App\Ticket\Properties\Number;
use App\Ticket\Properties\Status;
use App\Ticket\Properties\TeamId;
use App\Ticket\Properties\Title;
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketRelationship;
use App\Ticket\TicketStatusEnum;
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
     * Tickets newest first, optionally only open or closed ones, matching a
     * search on the title or the ticket number.
     *
     * @return LengthAwarePaginator<int, TicketEloquentModel>
     */
    public static function paginateForTeam(string $teamId, string $filter = self::FILTER_OPEN, ?string $search = null, ?string $customerId = null, int $perPage = 25, ?string $assigneeId = null): LengthAwarePaginator
    {
        return self::query($teamId)
            ->with([TicketRelationship::Customer->value, TicketRelationship::Assignee->value])
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
     * How many open tickets the team has, optionally only those assigned to one person.
     */
    public static function countOpenForTeam(string $teamId, ?string $assigneeId = null): int
    {
        return self::query($teamId)
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
