<?php

declare(strict_types=1);

namespace App\Shift\Actions;

use App\Attendance\AttendanceCalendar;
use App\Attendance\AttendanceTimezone;
use App\Shift\Notifications\ShiftStillOpenNotification;
use App\Shift\Properties\ClockedInAt;
use App\Shift\Properties\ClockedOutAt;
use App\Shift\Properties\RemindedAt;
use App\Shift\Properties\TeamId;
use App\Shift\ShiftEloquentModel;
use App\Team\Actions\FindTeam;
use App\Team\Scopes\CurrentTeamScope;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Tells members who are still clocked in two hours after their scheduled end,
 * or after twelve hours on a day they weren't scheduled. Each shift is
 * reminded about once. Nobody is clocked out for them: a guessed time would
 * end up in payroll. Suspended teams are skipped. Runs hourly.
 */
final class RemindOpenShifts
{
    public const int HOURS_AFTER_END = 2;

    public const int HOURS_WHEN_UNSCHEDULED = 12;

    /**
     * @return int how many reminders were sent
     */
    public static function run(?CarbonImmutable $now = null): int
    {
        $now ??= CarbonImmutable::now();

        return ShiftEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->whereNotIn(TeamId::getDatabaseTableColumnName(), FindTeam::suspendedIds())
            ->whereNull(ClockedOutAt::getDatabaseTableColumnName())
            ->whereNull(RemindedAt::getDatabaseTableColumnName())
            ->where(ClockedInAt::getDatabaseTableColumnName(), '<=', $now->subHours(self::HOURS_AFTER_END)->utc()->toDateTimeString())
            ->get()
            ->groupBy('team_id')
            ->sum(fn (Collection $shifts, string $teamId): int => self::remindTeam($teamId, $shifts, $now));
    }

    /**
     * @param  Collection<int, ShiftEloquentModel>  $shifts
     */
    private static function remindTeam(string $teamId, Collection $shifts, CarbonImmutable $now): int
    {
        $members = AttendanceTimezone::membersOfTeam($teamId);
        $workDates = [$now->toDateString(), ...$shifts->map(fn (ShiftEloquentModel $shift): string => $shift->work_date)->all()];
        $calendar = AttendanceCalendar::forTeam($teamId, $members, min($workDates), max($workDates));

        return $shifts
            ->filter(function (ShiftEloquentModel $shift) use ($members, $calendar, $now): bool {
                $timezone = $members->firstWhere('id', $shift->user_id)['timezone'] ?? AttendanceTimezone::forUserId($shift->user_id);
                $expected = $calendar->expectedOn($shift->user_id, $shift->work_date, $timezone);
                $dueAt = $expected->isWorkday() && $expected->endsAt instanceof CarbonImmutable
                    ? $expected->endsAt->addHours(self::HOURS_AFTER_END)
                    : $shift->clocked_in_at->addHours(self::HOURS_WHEN_UNSCHEDULED);

                if ($now->lessThan($dueAt)) {
                    return false;
                }

                // A query update, so the audit log isn't filled with reminders.
                ShiftEloquentModel::query()->withoutGlobalScopes()->whereKey($shift->id)->update([RemindedAt::getDatabaseTableColumnName() => $now->utc()->toDateTimeString()]);

                UserEloquentModel::query()->find($shift->user_id)?->notify(new ShiftStillOpenNotification(
                    $shift,
                    $shift->clocked_in_at->setTimezone($timezone)->translatedFormat('D j M \a\t g:i A'),
                ));

                return true;
            })
            ->count();
    }
}
