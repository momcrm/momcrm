<?php

declare(strict_types=1);

namespace App\Timesheet\Actions;

use App\Team\Scopes\CurrentTeamScope;
use App\Timesheet\Properties\EndsOn;
use App\Timesheet\Properties\Id;
use App\Timesheet\Properties\StartsOn;
use App\Timesheet\Properties\TeamId;
use App\Timesheet\Properties\UserId;
use App\Timesheet\Timesheet;
use App\Timesheet\TimesheetCollection;
use App\Timesheet\TimesheetEloquentModel;
use Illuminate\Database\Eloquent\Builder;

final class FindTimesheet
{
    public static function byIdForTeam(?string $id, ?string $teamId): Timesheet
    {
        if ($id === null || $teamId === null) {
            return Timesheet::new();
        }

        return Timesheet::fromEloquentModel(self::query($teamId)
            ->where(Id::getDatabaseTableColumnName(), $id)
            ->first());
    }

    /**
     * The member's timesheet for the period starting on this date, or an
     * empty one while it's still open.
     */
    public static function forMemberStartingOn(string $teamId, string $userId, string $startsOn): Timesheet
    {
        return Timesheet::fromEloquentModel(self::query($teamId)
            ->where(UserId::getDatabaseTableColumnName(), $userId)
            ->where(StartsOn::getDatabaseTableColumnName(), $startsOn)
            ->first());
    }

    /**
     * The member's timesheet whose period holds this date, or an empty one.
     */
    public static function coveringForMember(string $teamId, string $userId, string $date): Timesheet
    {
        return Timesheet::fromEloquentModel(self::query($teamId)
            ->where(UserId::getDatabaseTableColumnName(), $userId)
            ->where(StartsOn::getDatabaseTableColumnName(), '<=', $date)
            ->where(EndsOn::getDatabaseTableColumnName(), '>=', $date)
            ->first());
    }

    /**
     * Every timesheet for the periods starting from one date to another.
     */
    public static function startingBetweenForTeam(string $teamId, string $from, string $to): TimesheetCollection
    {
        return Timesheet::collect(self::query($teamId)
            ->whereBetween(StartsOn::getDatabaseTableColumnName(), [$from, $to])
            ->get()->all(), 'fromEloquentModel');
    }

    /**
     * The member's latest timesheet, or an empty one.
     */
    public static function latestForMember(string $teamId, string $userId): Timesheet
    {
        return Timesheet::fromEloquentModel(self::query($teamId)
            ->where(UserId::getDatabaseTableColumnName(), $userId)
            ->orderByDesc(StartsOn::getDatabaseTableColumnName())
            ->first());
    }

    /**
     * @return Builder<TimesheetEloquentModel>
     */
    private static function query(string $teamId): Builder
    {
        return TimesheetEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId);
    }
}
