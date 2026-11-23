<?php

declare(strict_types=1);

namespace App\Holiday\Actions;

use App\Holiday\Holiday;
use App\Holiday\HolidayCollection;
use App\Holiday\HolidayEloquentModel;
use App\Holiday\Properties\Date;
use App\Holiday\Properties\Id;
use App\Holiday\Properties\TeamId;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Database\Eloquent\Builder;

final class FindHoliday
{
    public static function byIdForTeam(?string $id, ?string $teamId): Holiday
    {
        if ($id === null || $teamId === null) {
            return Holiday::new();
        }

        return Holiday::fromEloquentModel(self::query($teamId)
            ->where(Id::getDatabaseTableColumnName(), $id)
            ->first());
    }

    /**
     * The team's holidays from one day on, earliest first.
     */
    public static function fromDateForTeam(string $teamId, string $from): HolidayCollection
    {
        return Holiday::collect(self::query($teamId)
            ->where(Date::getDatabaseTableColumnName(), '>=', $from)
            ->orderBy(Date::getDatabaseTableColumnName())
            ->get()->all(), 'fromEloquentModel');
    }

    /**
     * The team's holidays from one day to another, inclusive.
     */
    public static function betweenForTeam(string $teamId, string $from, string $to): HolidayCollection
    {
        return Holiday::collect(self::query($teamId)
            ->whereBetween(Date::getDatabaseTableColumnName(), [$from, $to])
            ->orderBy(Date::getDatabaseTableColumnName())
            ->get()->all(), 'fromEloquentModel');
    }

    /**
     * @return Builder<HolidayEloquentModel>
     */
    private static function query(string $teamId): Builder
    {
        return HolidayEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId);
    }
}
