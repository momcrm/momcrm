<?php

declare(strict_types=1);

namespace App\Attendance;

use App\Shift\Shift;
use App\Shift\ShiftCollection;
use App\ShiftBreak\ShiftBreakCollection;
use Carbon\CarbonImmutable;

/**
 * How one member's day went against what was expected of them. This is
 * worked out each time rather than stored, so changing a schedule changes
 * how past days read too.
 *
 * - present: worked, on time
 * - late: in after the start plus grace, or not in yet once that has passed
 * - left_early: clocked out for the day before the end less grace
 * - absent: a workday that ended with no time worked
 * - upcoming: a workday that has not started yet
 * - holiday, day_off, on_leave: nothing expected; any time worked is overtime
 * - unscheduled: the team has no schedules, so nothing is measured
 *
 * Flags list every problem (a day can be both late and left early) plus
 * overtime, which is time worked beyond what was expected by more than the
 * grace.
 */
final class AttendanceDay
{
    public const string PRESENT = 'present';

    public const string LATE = 'late';

    public const string LEFT_EARLY = 'left_early';

    public const string ABSENT = 'absent';

    public const string UPCOMING = 'upcoming';

    public const string OVERTIME = 'overtime';

    public const string ON_LEAVE = ExpectedDay::ON_LEAVE;

    /**
     * @param  ShiftCollection  $shifts  the member's shifts with this work date
     * @param  ShiftBreakCollection  $breaks  breaks of those shifts
     * @return array{status: string, flags: list<string>, holiday: ?string, leave: ?string, expected_starts_at: ?string, expected_ends_at: ?string, expected_seconds: int, worked_seconds: int, break_seconds: int, overtime_seconds: int, late_seconds: int}
     */
    public static function calculate(ExpectedDay $expected, ShiftCollection $shifts, ShiftBreakCollection $breaks, CarbonImmutable $now): array
    {
        $worked = 0;
        $onBreaks = 0;

        foreach ($shifts as $shift) {
            $own = $breaks->forShift($shift->getId()->toString());
            $worked += ShiftSummary::workedSeconds($shift, $own, $now);
            $onBreaks += $own->secondsUntil($shift->getClockedOutAt()->toNullableCarbon() ?? $now);
        }

        $expectedSeconds = $expected->expectedSeconds();
        $overtime = $expected->kind === ExpectedDay::UNSCHEDULED ? 0 : max(0, $worked - $expectedSeconds);
        $flags = [];
        $late = 0;

        if ($expected->isWorkday()) {
            [$flags, $late] = self::workdayFlags($expected, $shifts, $now);
        }

        if ($overtime > $expected->graceSeconds()) {
            $flags[] = self::OVERTIME;
        }

        return [
            'status' => self::status($expected, $flags, $shifts, $now),
            'flags' => $flags,
            'holiday' => $expected->holiday,
            'leave' => $expected->leave,
            'expected_starts_at' => $expected->startsAt?->toISOString(),
            'expected_ends_at' => $expected->endsAt?->toISOString(),
            'expected_seconds' => $expectedSeconds,
            'worked_seconds' => $worked,
            'break_seconds' => $onBreaks,
            'overtime_seconds' => $overtime,
            'late_seconds' => $late,
        ];
    }

    /**
     * @return array{0: list<string>, 1: int}
     */
    private static function workdayFlags(ExpectedDay $expected, ShiftCollection $shifts, CarbonImmutable $now): array
    {
        $startsAt = $expected->startsAt ?? $now;
        $endsAt = $expected->endsAt ?? $now;
        $graceEndsAt = $startsAt->addSeconds($expected->graceSeconds());

        if ($shifts->count() === 0) {
            return match (true) {
                $now->greaterThanOrEqualTo($endsAt) => [[self::ABSENT], 0],
                $now->greaterThan($graceEndsAt) => [[self::LATE], (int) $startsAt->diffInSeconds($now)],
                default => [[], 0],
            };
        }

        $flags = [];
        $late = 0;
        $firstIn = $shifts->min(fn (Shift $shift): int => $shift->getClockedInAt()->toCarbon()->getTimestamp());

        if (is_int($firstIn) && $firstIn > $graceEndsAt->getTimestamp()) {
            $flags[] = self::LATE;
            $late = $firstIn - $startsAt->getTimestamp();
        }

        $isWorking = $shifts->contains(fn (Shift $shift): bool => $shift->isOpen());
        $lastOut = $shifts->max(fn (Shift $shift): int => $shift->getClockedOutAt()->toNullableCarbon()?->getTimestamp() ?? 0);

        if (! $isWorking && is_int($lastOut) && $lastOut < $endsAt->getTimestamp() - $expected->graceSeconds()) {
            $flags[] = self::LEFT_EARLY;
        }

        return [$flags, $late];
    }

    /**
     * The one word for the day: what kind of day it was, or its worst flag.
     *
     * @param  list<string>  $flags
     */
    private static function status(ExpectedDay $expected, array $flags, ShiftCollection $shifts, CarbonImmutable $now): string
    {
        if (! $expected->isWorkday()) {
            return $expected->kind;
        }

        foreach ([self::ABSENT, self::LATE, self::LEFT_EARLY] as $flag) {
            if (in_array($flag, $flags, true)) {
                return $flag;
            }
        }

        return $shifts->count() === 0 && $now->lessThan($expected->endsAt ?? $now) ? self::UPCOMING : self::PRESENT;
    }
}
