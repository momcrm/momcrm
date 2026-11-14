<?php

declare(strict_types=1);

namespace App\Activity\Actions;

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityRelationship;
use App\Activity\Properties\CreatedAt;
use App\Activity\Properties\Id;
use App\Activity\Properties\TeamId;
use App\Activity\Properties\UserId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class FindActivities
{
    public const int PER_PAGE = 25;

    /**
     * A team's rows, newest first.
     *
     * @return LengthAwarePaginator<int, ActivityEloquentModel>
     */
    public static function forTeam(string $teamId, string $pageName = 'page'): LengthAwarePaginator
    {
        return self::newestFirst(
            ActivityEloquentModel::query()->where(TeamId::getDatabaseTableColumnName(), $teamId),
            $pageName,
        );
    }

    /**
     * A user's own account-level rows (not tied to a team), newest first.
     *
     * @return LengthAwarePaginator<int, ActivityEloquentModel>
     */
    public static function forUser(string $userId, string $pageName = 'page'): LengthAwarePaginator
    {
        return self::newestFirst(
            ActivityEloquentModel::query()
                ->where(UserId::getDatabaseTableColumnName(), $userId)
                ->whereNull(TeamId::getDatabaseTableColumnName()),
            $pageName,
        );
    }

    /**
     * @param  Builder<ActivityEloquentModel>  $query
     * @return LengthAwarePaginator<int, ActivityEloquentModel>
     */
    private static function newestFirst(Builder $query, string $pageName): LengthAwarePaginator
    {
        return $query
            ->with(ActivityRelationship::Actor->value)
            ->orderByDesc(CreatedAt::getDatabaseTableColumnName())
            ->orderByDesc(Id::getDatabaseTableColumnName())
            ->paginate(perPage: self::PER_PAGE, pageName: $pageName);
    }
}
