<?php

declare(strict_types=1);

namespace App\Rate\Actions;

use App\Rate\Properties\ArchivedAt;
use App\Rate\Properties\Name;
use App\Rate\Properties\TeamId;
use App\Rate\Rate;
use App\Rate\RateCollection;
use App\Rate\RateEloquentModel;
use Illuminate\Database\Eloquent\Builder;

final class FindRate
{
    /**
     * A team's rates by name. Archived rates are left out unless asked for,
     * since they are only kept for the services still on them.
     */
    public static function listForTeam(string $teamId, bool $withArchived = false): RateCollection
    {
        return Rate::collect(
            RateEloquentModel::query()
                ->withoutGlobalScopes()
                ->where(TeamId::getDatabaseTableColumnName(), $teamId)
                ->when(! $withArchived, fn (Builder $query) => $query->whereNull(ArchivedAt::getDatabaseTableColumnName()))
                ->orderBy(Name::getDatabaseTableColumnName())
                ->get()
                ->all(),
            'fromEloquentModel',
        );
    }

    /**
     * One of a team's rates by id, archived or not; empty if it isn't the team's.
     */
    public static function byIdForTeam(?string $id, string $teamId): Rate
    {
        if ($id === null) {
            return Rate::new();
        }

        $model = RateEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->find($id);

        return $model === null ? Rate::new() : Rate::fromEloquentModel($model);
    }
}
