<?php

declare(strict_types=1);

namespace App\WorkSchedule\Actions;

use App\Team\Scopes\CurrentTeamScope;
use App\WorkSchedule\Properties\Id;
use App\WorkSchedule\Properties\Name;
use App\WorkSchedule\Properties\TeamId;
use App\WorkSchedule\WorkSchedule;
use App\WorkSchedule\WorkScheduleCollection;
use App\WorkSchedule\WorkScheduleEloquentModel;
use Illuminate\Database\Eloquent\Builder;

final class FindWorkSchedule
{
    public static function byIdForTeam(?string $id, ?string $teamId): WorkSchedule
    {
        if ($id === null || $teamId === null) {
            return WorkSchedule::new();
        }

        return WorkSchedule::fromEloquentModel(self::query($teamId)
            ->where(Id::getDatabaseTableColumnName(), $id)
            ->first());
    }

    /**
     * The team's schedules by name.
     */
    public static function listForTeam(string $teamId): WorkScheduleCollection
    {
        return WorkSchedule::collect(self::query($teamId)
            ->orderBy(Name::getDatabaseTableColumnName())
            ->get()->all(), 'fromEloquentModel');
    }

    /**
     * @return Builder<WorkScheduleEloquentModel>
     */
    private static function query(string $teamId): Builder
    {
        return WorkScheduleEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId);
    }
}
