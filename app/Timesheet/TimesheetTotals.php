<?php

declare(strict_types=1);

namespace App\Timesheet;

use App\Attendance\AttendanceCalendar;
use App\Attendance\AttendanceDay;
use App\Attendance\AttendancePeriod;
use App\Attendance\AttendanceTimezone;
use Carbon\CarbonImmutable;

/**
 * What a member's period adds up to. An approved timesheet keeps what it
 * added up to when approved; anything else is worked out from the shifts,
 * schedules and leave as they are now.
 */
final class TimesheetTotals
{
    /**
     * The period's totals and days, without each day's shifts.
     *
     * @return array{starts_on: string, ends_on: string, worked_seconds: int, break_seconds: int, expected_seconds: int, overtime_seconds: int, leave_days: float, days: list<array<string, mixed>>}
     */
    public static function live(string $teamId, string $userId, string $from, string $to, ?AttendanceCalendar $calendar = null, ?CarbonImmutable $now = null): array
    {
        $calendar ??= AttendanceCalendar::forTeam($teamId, AttendanceTimezone::membersOfTeam($teamId), $from, $to);
        $period = AttendancePeriod::forMember($teamId, $userId, $from, $to, AttendanceTimezone::forUserId($userId), $calendar, $now ?? CarbonImmutable::now());

        return [
            ...$period,
            'days' => array_map(fn (array $day): array => array_diff_key($day, ['shifts' => true]), $period['days']),
        ];
    }

    /**
     * The timesheet's totals as approved, or as they are now.
     *
     * @return array<string, mixed>
     */
    public static function of(Timesheet $timesheet, string $teamId, string $userId, string $from, string $to, ?AttendanceCalendar $calendar = null): array
    {
        $stored = $timesheet->getTotals()->toNullableArray();

        if ($timesheet->getStatus()->toNullableEnum() === TimesheetStatusEnum::Approved && is_array($stored) && $stored !== []) {
            return array_filter($stored, is_string(...), ARRAY_FILTER_USE_KEY);
        }

        return self::live($teamId, $userId, $from, $to, $calendar);
    }

    /**
     * The headline numbers of some totals, with how many days were late or absent.
     *
     * @param  array<string, mixed>  $totals
     * @return array{worked_seconds: int, expected_seconds: int, overtime_seconds: int, leave_days: float, late_days: int, absent_days: int}
     */
    public static function headline(array $totals): array
    {
        $days = is_array($totals['days'] ?? null) ? $totals['days'] : [];
        $flagged = fn (string $flag): int => count(array_filter($days, fn (mixed $day): bool => is_array($day) && is_array($day['flags'] ?? null) && in_array($flag, $day['flags'], true)));

        return [
            'worked_seconds' => is_int($totals['worked_seconds'] ?? null) ? $totals['worked_seconds'] : 0,
            'expected_seconds' => is_int($totals['expected_seconds'] ?? null) ? $totals['expected_seconds'] : 0,
            'overtime_seconds' => is_int($totals['overtime_seconds'] ?? null) ? $totals['overtime_seconds'] : 0,
            'leave_days' => is_numeric($totals['leave_days'] ?? null) ? (float) $totals['leave_days'] : 0.0,
            'late_days' => $flagged(AttendanceDay::LATE),
            'absent_days' => $flagged(AttendanceDay::ABSENT),
        ];
    }
}
