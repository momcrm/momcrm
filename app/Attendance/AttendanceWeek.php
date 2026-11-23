<?php

declare(strict_types=1);

namespace App\Attendance;

use Carbon\CarbonImmutable;

/**
 * One member's week, Monday to Sunday: each day's shifts and breaks, how the
 * day went against their schedule, and the week's totals.
 */
final class AttendanceWeek
{
    /**
     * The Monday of the week holding this day.
     */
    public static function startOf(CarbonImmutable $day): CarbonImmutable
    {
        return $day->startOfDay()->startOfWeek(CarbonImmutable::MONDAY);
    }

    /**
     * @return array{starts_on: string, ends_on: string, worked_seconds: int, break_seconds: int, expected_seconds: int, overtime_seconds: int, leave_days: float, days: list<array<string, mixed>>}
     */
    public static function forMember(string $teamId, string $userId, CarbonImmutable $monday, string $timezone, AttendanceCalendar $calendar, CarbonImmutable $now): array
    {
        return AttendancePeriod::forMember($teamId, $userId, $monday->toDateString(), $monday->addDays(6)->toDateString(), $timezone, $calendar, $now);
    }
}
