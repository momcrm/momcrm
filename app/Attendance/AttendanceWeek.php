<?php

declare(strict_types=1);

namespace App\Attendance;

use App\Shift\Actions\FindShift;
use App\Shift\Shift;
use App\ShiftBreak\Actions\FindShiftBreak;
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
     * @return array{starts_on: string, ends_on: string, worked_seconds: int, break_seconds: int, expected_seconds: int, overtime_seconds: int, days: list<array<string, mixed>>}
     */
    public static function forMember(string $teamId, string $userId, CarbonImmutable $monday, string $timezone, AttendanceCalendar $calendar, CarbonImmutable $now): array
    {
        $from = $monday->toDateString();
        $to = $monday->addDays(6)->toDateString();
        $shifts = FindShift::forMemberBetween($teamId, $userId, $from, $to);
        $breaks = FindShiftBreak::forShifts($shifts->ids());

        $days = [];

        for ($offset = 0; $offset < 7; $offset++) {
            $date = $monday->addDays($offset)->toDateString();
            $ofDay = $shifts->filter(fn (Shift $shift): bool => $shift->getWorkDate()->toDateString() === $date)->values();

            $days[] = [
                'date' => $date,
                ...AttendanceDay::calculate($calendar->expectedOn($userId, $date, $timezone), $ofDay, $breaks, $now),
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
            'days' => $days,
        ];
    }
}
