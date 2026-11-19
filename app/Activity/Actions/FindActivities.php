<?php

declare(strict_types=1);

namespace App\Activity\Actions;

use App\Activity\Activity;
use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityRelationship;
use App\Activity\Properties\CreatedAt;
use App\Activity\Properties\Id;
use App\Activity\Properties\SubjectId;
use App\Activity\Properties\SubjectType;
use App\Activity\Properties\TeamId;
use App\Activity\Properties\UserId;
use App\Shared\MorphMapKeyEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class FindActivities
{
    public const int PER_PAGE = 25;

    /**
     * A team's rows, newest first.
     *
     * @return LengthAwarePaginator<int, Activity>
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
     * @return LengthAwarePaginator<int, Activity>
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
     * One record's rows within a team, such as a service's, newest first.
     *
     * @return LengthAwarePaginator<int, Activity>
     */
    public static function forSubject(string $teamId, MorphMapKeyEnum $subjectType, string $subjectId, string $pageName = 'page'): LengthAwarePaginator
    {
        return self::newestFirst(
            ActivityEloquentModel::query()
                ->where(TeamId::getDatabaseTableColumnName(), $teamId)
                ->where(SubjectType::getDatabaseTableColumnName(), $subjectType->value)
                ->where(SubjectId::getDatabaseTableColumnName(), $subjectId),
            $pageName,
        );
    }

    /**
     * @param  Builder<ActivityEloquentModel>  $query
     * @return LengthAwarePaginator<int, Activity>
     */
    private static function newestFirst(Builder $query, string $pageName): LengthAwarePaginator
    {
        return $query
            ->with(ActivityRelationship::Actor->value)
            ->orderByDesc(CreatedAt::getDatabaseTableColumnName())
            ->orderByDesc(Id::getDatabaseTableColumnName())
            ->paginate(perPage: self::PER_PAGE, pageName: $pageName)
            ->through(fn (ActivityEloquentModel $model): Activity => Activity::fromEloquentModel($model));
    }
}
