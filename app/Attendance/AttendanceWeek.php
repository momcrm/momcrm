<?php

declare(strict_types=1);

namespace App\Attendance;

use App\Shift\Actions\FindShift;
use App\Shift\Shift;
use App\ShiftBreak\Actions\FindShiftBreak;
use Carbon\CarbonImmutable;

/**
 * One member's week, Monday to Sunday: each day's shifts and breaks with the
 * time worked, and the week's total.
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
     * @return array{starts_on: string, ends_on: string, worked_seconds: int, break_seconds: int, days: list<array{date: string, worked_seconds: int, break_seconds: int, shifts: list<array<string, mixed>>}>}
     */
    public static function forMember(string $teamId, string $userId, CarbonImmutable $monday, string $timezone, CarbonImmutable $now): array
    {
        $from = $monday->toDateString();
        $to = $monday->addDays(6)->toDateString();
        $shifts = FindShift::forMemberBetween($teamId, $userId, $from, $to);
        $breaks = FindShiftBreak::forShifts($shifts->ids());

        $days = [];

        for ($offset = 0; $offset < 7; $offset++) {
            $date = $monday->addDays($offset)->toDateString();
            $summaries = array_values($shifts
                ->filter(fn (Shift $shift): bool => $shift->getWorkDate()->toDateString() === $date)
                ->map(fn (Shift $shift): array => ShiftSummary::toArray($shift, $breaks, $timezone, $now))
                ->all());

            $days[] = [
                'date' => $date,
                'worked_seconds' => array_sum(array_column($summaries, 'worked_seconds')),
                'break_seconds' => array_sum(array_column($summaries, 'break_seconds')),
                'shifts' => $summaries,
            ];
        }

        return [
            'starts_on' => $from,
            'ends_on' => $to,
            'worked_seconds' => array_sum(array_column($days, 'worked_seconds')),
            'break_seconds' => array_sum(array_column($days, 'break_seconds')),
            'days' => $days,
        ];
    }
}
