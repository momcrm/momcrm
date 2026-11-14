<?php

declare(strict_types=1);

namespace App\Project\Actions;

use App\Project\Project;
use App\Project\ProjectEloquentModel;
use App\Project\ProjectRelationship;
use App\Project\ProjectVisibilityEnum;
use App\Project\Properties\ArchivedAt;
use App\Project\Properties\CustomerId;
use App\Project\Properties\DueOn;
use App\Project\Properties\Id;
use App\Project\Properties\Name;
use App\Project\Properties\Status;
use App\Project\Properties\TeamId;
use App\Project\Properties\Visibility;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use App\Ticket\Properties\ArchivedAt as TicketArchivedAt;
use App\Ticket\Properties\ParentId as TicketParentId;
use App\Ticket\Properties\Status as TicketStatus;
use App\Ticket\TicketStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

final class FindProject
{
    /**
     * Columns the project list can sort by.
     *
     * @var array<int, string>
     */
    public const array SORTS = ['name', 'due_on', 'updated_at', 'status'];

    public static function byIdForTeam(?string $id, ?string $teamId): Project
    {
        if ($id === null || $teamId === null) {
            return Project::new();
        }

        return Project::fromEloquentModel(self::query($teamId)->where(Id::getDatabaseTableColumnName(), $id)->first());
    }

    /**
     * A project someone may see: any open project of the team, or a restricted
     * one they are a member of.
     */
    public static function visibleByIdForTeam(?string $id, ?string $teamId, string $userId): Project
    {
        if ($id === null || $teamId === null) {
            return Project::new();
        }

        return Project::fromEloquentModel(
            self::visibleQuery($teamId, $userId)->where(Id::getDatabaseTableColumnName(), $id)->first(),
        );
    }

    /**
     * A project someone may see, with its lead, customer, members and ticket
     * counts, for its page.
     */
    public static function withDetailsForTeam(string $id, string $teamId, string $userId): ?ProjectEloquentModel
    {
        return self::withListDetails(self::visibleQuery($teamId, $userId))
            ->with(ProjectRelationship::Group->value)
            ->where(Id::getDatabaseTableColumnName(), $id)
            ->first();
    }

    /**
     * The team's live projects someone may see, with their lead, customer,
     * members and ticket counts.
     *
     * @return EloquentCollection<int, ProjectEloquentModel>
     */
    public static function listForTeam(string $teamId, string $userId, ?string $search = null, ?string $status = null, string $sort = 'name', string $direction = 'asc'): EloquentCollection
    {
        $query = self::withListDetails(self::visibleQuery($teamId, $userId))
            ->whereNull(ArchivedAt::getDatabaseTableColumnName())
            ->when($search, fn (Builder $query, string $search) => $query->where(Name::getDatabaseTableColumnName(), 'like', "%{$search}%"))
            ->when($status, fn (Builder $query, string $status) => $query->where(Status::getDatabaseTableColumnName(), $status));

        $query = match ($sort) {
            'due_on' => $query->orderByRaw($direction === 'asc' ? 'due_on is null, due_on asc' : 'due_on is null, due_on desc'),
            'updated_at' => $query->orderBy('updated_at', $direction === 'asc' ? 'asc' : 'desc'),
            'status' => $query->orderBy(Status::getDatabaseTableColumnName(), $direction === 'asc' ? 'asc' : 'desc'),
            default => $query->orderBy(Name::getDatabaseTableColumnName(), $direction === 'asc' ? 'asc' : 'desc'),
        };

        return $query->limit(200)->get();
    }

    /**
     * A customer's live projects someone may see.
     *
     * @return EloquentCollection<int, ProjectEloquentModel>
     */
    public static function forCustomer(string $teamId, string $userId, string $customerId): EloquentCollection
    {
        return self::withListDetails(self::visibleQuery($teamId, $userId))
            ->whereNull(ArchivedAt::getDatabaseTableColumnName())
            ->where(CustomerId::getDatabaseTableColumnName(), $customerId)
            ->orderBy(Name::getDatabaseTableColumnName())
            ->get();
    }

    /**
     * Archived projects someone may see, most recently archived first.
     *
     * @return EloquentCollection<int, ProjectEloquentModel>
     */
    public static function archivedForTeam(string $teamId, string $userId): EloquentCollection
    {
        return self::withListDetails(self::visibleQuery($teamId, $userId))
            ->whereNotNull(ArchivedAt::getDatabaseTableColumnName())
            ->orderByDesc(ArchivedAt::getDatabaseTableColumnName())
            ->get();
    }

    /**
     * Some projects by id, in the order given, for favourites and the sidebar.
     *
     * @param  array<int, string>  $ids
     * @return EloquentCollection<int, ProjectEloquentModel>
     */
    public static function liveByIds(string $teamId, string $userId, array $ids): EloquentCollection
    {
        return self::withListDetails(self::visibleQuery($teamId, $userId))
            ->whereNull(ArchivedAt::getDatabaseTableColumnName())
            ->whereIn(Id::getDatabaseTableColumnName(), $ids)
            ->get()
            ->sortBy(fn (ProjectEloquentModel $project): int|false => array_search($project->id, $ids, true))
            ->values();
    }

    /**
     * The projects the sidebar lists: starred ones first, then the most
     * recently updated, up to a handful.
     *
     * @param  array<int, string>  $favoriteIds
     * @return Collection<int, array{id: string, name: string, color: string, status: string, is_favorite: bool}>
     */
    public static function forSidebar(string $teamId, string $userId, array $favoriteIds, int $limit = 6): Collection
    {
        $live = fn (): Builder => self::visibleQuery($teamId, $userId)->whereNull(ArchivedAt::getDatabaseTableColumnName());

        $starred = $live()->whereIn(Id::getDatabaseTableColumnName(), $favoriteIds)->orderBy(Name::getDatabaseTableColumnName())->limit($limit)->get();
        $recent = $live()
            ->whereNotIn(Id::getDatabaseTableColumnName(), $favoriteIds)
            ->orderByDesc('updated_at')
            ->limit(max(0, $limit - $starred->count()))
            ->get();

        return $starred->concat($recent)
            ->map(fn (ProjectEloquentModel $project): array => [
                'id' => $project->id,
                'name' => $project->name,
                'color' => $project->color,
                'status' => $project->status,
                'is_favorite' => in_array($project->id, $favoriteIds, true),
            ])
            ->values();
    }

    /**
     * The team's live projects as pickers need them: id, name, colour and customer.
     *
     * @return Collection<int, array{id: string, name: string, color: string, customer_id: ?string}>
     */
    public static function optionsForTeam(string $teamId): Collection
    {
        return self::query($teamId)
            ->whereNull(ArchivedAt::getDatabaseTableColumnName())
            ->orderBy(Name::getDatabaseTableColumnName())
            ->get()
            ->map(fn (ProjectEloquentModel $project): array => [
                'id' => $project->id,
                'name' => $project->name,
                'color' => $project->color,
                'customer_id' => $project->customer_id,
            ]);
    }

    /**
     * @param  Builder<ProjectEloquentModel>  $query
     * @return Builder<ProjectEloquentModel>
     */
    private static function withListDetails(Builder $query): Builder
    {
        $liveTickets = fn (Builder $query) => $query
            ->whereNull(TicketArchivedAt::getDatabaseTableColumnName())
            ->whereNull(TicketParentId::getDatabaseTableColumnName());

        return $query
            ->with([
                ProjectRelationship::Customer->value,
                ProjectRelationship::Lead->value,
                ProjectRelationship::Members->value,
            ])
            ->withCount([
                ProjectRelationship::Tickets->value => $liveTickets,
                ProjectRelationship::Tickets->value.' as closed_tickets_count' => fn (Builder $query) => $liveTickets($query)
                    ->where(TicketStatus::getDatabaseTableColumnName(), TicketStatusEnum::Closed->value),
                ProjectRelationship::Tickets->value.' as overdue_tickets_count' => fn (Builder $query) => $liveTickets($query)
                    ->whereIn(TicketStatus::getDatabaseTableColumnName(), TicketStatusEnum::openValues())
                    ->where(DueOn::getDatabaseTableColumnName(), '<', now()->toDateString()),
            ]);
    }

    /**
     * @return Builder<ProjectEloquentModel>
     */
    private static function visibleQuery(string $teamId, string $userId): Builder
    {
        return self::query($teamId)->where(fn (Builder $query) => $query
            ->where(Visibility::getDatabaseTableColumnName(), ProjectVisibilityEnum::Open->value)
            ->orWhereExists(fn ($members) => $members
                ->selectRaw('1')
                ->from(Table::ProjectUser->value)
                ->whereColumn(Table::ProjectUser->value.'.project_id', Table::Projects->value.'.id')
                ->where(Table::ProjectUser->value.'.user_id', $userId)));
    }

    /**
     * @return Builder<ProjectEloquentModel>
     */
    private static function query(string $teamId): Builder
    {
        return ProjectEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId);
    }
}
