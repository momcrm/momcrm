<?php

declare(strict_types=1);

namespace App\Project;

use App\Shared\MorphMapKeyEnum;
use App\Shared\Table;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Builder;

/**
 * Who may see what in members-only projects. An open project is visible to the
 * whole team; a restricted one only to its members. A ticket in a restricted
 * project is visible to the project's members and to whoever it is assigned to.
 */
final class ProjectVisibility
{
    /**
     * Limits a projects query to the ones someone may see.
     */
    public static function constrainProjects(QueryBuilder $query, string $userId, string $table = 'projects'): void
    {
        $query->where(fn (QueryBuilder $query) => $query
            ->where("{$table}.visibility", ProjectVisibilityEnum::Open->value)
            ->orWhereExists(fn (Builder $members) => $members
                ->selectRaw('1')
                ->from(Table::ProjectUser->value)
                ->whereColumn(Table::ProjectUser->value.'.project_id', "{$table}.id")
                ->where(Table::ProjectUser->value.'.user_id', $userId)));
    }

    /**
     * Limits a tickets query to the ones someone may see.
     */
    public static function constrainTickets(QueryBuilder $query, string $userId, string $table = 'tickets'): void
    {
        $query->where(fn (QueryBuilder $query) => $query
            ->whereNull("{$table}.project_id")
            ->orWhere("{$table}.assignee_id", $userId)
            ->orWhereIn("{$table}.project_id", fn (Builder $projects) => self::visibleProjectIds($projects, $userId)));
    }

    /**
     * Leaves out activity about projects, and tickets in projects, someone may not see.
     */
    public static function constrainActivities(QueryBuilder $query, string $userId): void
    {
        $query
            ->where(fn (QueryBuilder $query) => $query
                ->where('subject_type', '!=', MorphMapKeyEnum::Project->value)
                ->orWhereNull('subject_type')
                ->orWhereIn('subject_id', fn (Builder $projects) => self::visibleProjectIds($projects, $userId)))
            ->where(fn (QueryBuilder $query) => $query
                ->where('subject_type', '!=', MorphMapKeyEnum::Ticket->value)
                ->orWhereNull('subject_type')
                ->orWhereIn('subject_id', function (Builder $tickets) use ($userId): void {
                    $tickets->select('id')->from(Table::Tickets->value);
                    self::constrainTickets($tickets, $userId);
                }));
    }

    private static function visibleProjectIds(Builder $projects, string $userId): void
    {
        $projects->select('id')->from(Table::Projects->value);
        self::constrainProjects($projects, $userId);
    }
}
