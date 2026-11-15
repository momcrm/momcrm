<?php

declare(strict_types=1);

namespace App\AuditLog\Actions;

use App\AuditLog\AuditLog;
use App\AuditLog\AuditLogEloquentModel;
use App\AuditLog\AuditLogRelationship;
use App\AuditLog\Properties\CreatedAt;
use App\AuditLog\Properties\Id;
use App\AuditLog\Properties\TeamId;
use App\AuditLog\Properties\UserId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class FindAuditLogs
{
    public const int PER_PAGE = 25;

    /**
     * A team's rows, newest first.
     *
     * @return LengthAwarePaginator<int, AuditLog>
     */
    public static function forTeam(string $teamId, string $pageName = 'page'): LengthAwarePaginator
    {
        return self::newestFirst(
            AuditLogEloquentModel::query()->where(TeamId::getDatabaseTableColumnName(), $teamId),
            $pageName,
        );
    }

    /**
     * A user's own account-level rows (not tied to a team), newest first.
     *
     * @return LengthAwarePaginator<int, AuditLog>
     */
    public static function forUser(string $userId, string $pageName = 'page'): LengthAwarePaginator
    {
        return self::newestFirst(
            AuditLogEloquentModel::query()
                ->where(UserId::getDatabaseTableColumnName(), $userId)
                ->whereNull(TeamId::getDatabaseTableColumnName()),
            $pageName,
        );
    }

    /**
     * @param  Builder<AuditLogEloquentModel>  $query
     * @return LengthAwarePaginator<int, AuditLog>
     */
    private static function newestFirst(Builder $query, string $pageName): LengthAwarePaginator
    {
        return $query
            ->with(AuditLogRelationship::Actor->value)
            ->orderByDesc(CreatedAt::getDatabaseTableColumnName())
            ->orderByDesc(Id::getDatabaseTableColumnName())
            ->paginate(perPage: self::PER_PAGE, pageName: $pageName)
            ->through(fn (AuditLogEloquentModel $model): AuditLog => AuditLog::fromEloquentModel($model));
    }
}
