<?php

declare(strict_types=1);

namespace App\Shift\Actions;

use App\Shift\Properties\ClockedInAt;
use App\Shift\Properties\ClockedOutAt;
use App\Shift\Properties\Id;
use App\Shift\Properties\TeamId;
use App\Shift\Properties\UserId;
use App\Shift\Properties\WorkDate;
use App\Shift\Shift;
use App\Shift\ShiftCollection;
use App\Shift\ShiftEloquentModel;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class FindShift
{
    /**
     * A shift in the team, or an empty one.
     */
    public static function byIdForTeam(?string $id, ?string $teamId): Shift
    {
        if ($id === null || $teamId === null) {
            return Shift::new();
        }

        return Shift::fromEloquentModel(self::query($teamId)
            ->where(Id::getDatabaseTableColumnName(), $id)
            ->first());
    }

    /**
     * The shift the member is working now, or an empty one.
     */
    public static function openForMember(string $teamId, string $userId): Shift
    {
        return Shift::fromEloquentModel(self::query($teamId)
            ->where(UserId::getDatabaseTableColumnName(), $userId)
            ->whereNull(ClockedOutAt::getDatabaseTableColumnName())
            ->first());
    }

    /**
     * Every shift with a work date from one day to another, inclusive, plus
     * any still open from an earlier one, earliest first.
     */
    public static function forTeamBetweenOrOpen(string $teamId, string $from, string $to): ShiftCollection
    {
        return Shift::collect(self::query($teamId)
            ->where(fn (Builder $query) => $query
                ->whereBetween(WorkDate::getDatabaseTableColumnName(), [$from, $to])
                ->orWhereNull(ClockedOutAt::getDatabaseTableColumnName()))
            ->orderBy(ClockedInAt::getDatabaseTableColumnName())
            ->get()->all(), 'fromEloquentModel');
    }

    /**
     * A member's shifts with work dates from one day to another, inclusive,
     * earliest first.
     */
    public static function forMemberBetween(string $teamId, string $userId, string $from, string $to): ShiftCollection
    {
        return Shift::collect(self::query($teamId)
            ->where(UserId::getDatabaseTableColumnName(), $userId)
            ->whereBetween(WorkDate::getDatabaseTableColumnName(), [$from, $to])
            ->orderBy(ClockedInAt::getDatabaseTableColumnName())
            ->get()->all(), 'fromEloquentModel');
    }

    /**
     * Whether another of the member's shifts covers part of this time. An
     * open shift runs on without end, and so does the one being checked when
     * it has no clock-out.
     */
    public static function overlapsForMember(string $teamId, string $userId, CarbonImmutable $from, ?CarbonImmutable $to, ?string $exceptId = null): bool
    {
        return self::query($teamId)
            ->where(UserId::getDatabaseTableColumnName(), $userId)
            ->when($exceptId !== null, fn (Builder $query) => $query->where(Id::getDatabaseTableColumnName(), '!=', $exceptId))
            ->when($to !== null, fn (Builder $query) => $query->where(ClockedInAt::getDatabaseTableColumnName(), '<', $to?->utc()->toDateTimeString()))
            ->where(fn (Builder $query) => $query
                ->whereNull(ClockedOutAt::getDatabaseTableColumnName())
                ->orWhere(ClockedOutAt::getDatabaseTableColumnName(), '>', $from->utc()->toDateTimeString()))
            ->exists();
    }

    /**
     * @return Builder<ShiftEloquentModel>
     */
    private static function query(string $teamId): Builder
    {
        return ShiftEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId);
    }
}
