<?php

declare(strict_types=1);

namespace App\Attendance;

use App\Shift\Actions\FindShift;
use App\Shift\Shift;
use App\ShiftBreak\Actions\FindShiftBreak;
use Carbon\CarbonImmutable;

/**
 * One member's days from one date to another: each day's shifts, how it went
 * against their schedule, and the totals. The week view and timesheets both
 * read it.
 */
final class AttendancePeriod
{
    /**
     * @return array{starts_on: string, ends_on: string, worked_seconds: int, break_seconds: int, expected_seconds: int, overtime_seconds: int, leave_days: float, days: list<array<string, mixed>>}
     */
    public static function forMember(string $teamId, string $userId, string $from, string $to, string $timezone, AttendanceCalendar $calendar, CarbonImmutable $now): array
    {
        $shifts = FindShift::forMemberBetween($teamId, $userId, $from, $to);
        $breaks = FindShiftBreak::forShifts($shifts->ids());

        $days = [];

        for ($date = CarbonImmutable::parse($from); $date->toDateString() <= $to; $date = $date->addDay()) {
            $day = $date->toDateString();
            $ofDay = $shifts->filter(fn (Shift $shift): bool => $shift->getWorkDate()->toDateString() === $day)->values();

            $days[] = [
                'date' => $day,
                ...AttendanceDay::calculate($calendar->expectedOn($userId, $day, $timezone), $ofDay, $breaks, $now),
                'shifts' => array_values($ofDay->map(fn (Shift $shift): array => ShiftSummary::toArray($shift, $breaks, $timezone, $now))->all()),
            ];
        }

        return [
            'starts_on' => $from,
            'ends_on' => $to,
            'worked_seconds' => array_sum(array_column($days, 'worked_seconds')),
            'break_seconds' => array_sum(array_column($days, 'break_seconds')),
            'expected_seconds' => array_sum(array_column($days, 'expected_seconds')),
            'overtime_seconds' => array_sum(array_column($days, 'overtime_seconds')),
            'leave_days' => (float) array_sum(array_map(
                fn (array $day): float => match (true) {
                    $day['status'] === AttendanceDay::ON_LEAVE => 1.0,
                    $day['leave'] !== null => 0.5,
                    default => 0.0,
                },
                $days,
            )),
            'days' => $days,
        ];
    }
}
