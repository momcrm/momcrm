<?php

declare(strict_types=1);

use App\Attendance\AttendanceDay;
use App\Attendance\ExpectedDay;
use App\Shift\Shift;
use App\Shift\ShiftCollection;
use App\ShiftBreak\ShiftBreakCollection;
use Carbon\CarbonImmutable;

/**
 * Monday 23 November 2026, 9 to 5 UTC with 5 minutes’ grace.
 */
function nineToFive(int $breakMinutes = 0): ExpectedDay
{
    return new ExpectedDay(
        ExpectedDay::WORKDAY,
        startsAt: CarbonImmutable::parse('2026-11-23 09:00'),
        endsAt: CarbonImmutable::parse('2026-11-23 17:00'),
        breakMinutes: $breakMinutes,
        graceMinutes: 5,
    );
}

/**
 * The shifts worked that day, as [clock-in, clock-out or null while open] times.
 *
 * @param  list<array{0: string, 1: ?string}>  $times
 * @return array<string, mixed>
 */
function dayWith(ExpectedDay $expected, array $times, string $now): array
{
    $shifts = array_map(fn (array $time): Shift => Shift::new()
        ->setId(fake()->uuid())
        ->setClockedInAt(CarbonImmutable::parse("2026-11-23 {$time[0]}"))
        ->setClockedOutAt($time[1] === null ? null : CarbonImmutable::parse("2026-11-23 {$time[1]}")), $times);

    return AttendanceDay::calculate($expected, ShiftCollection::make($shifts), ShiftBreakCollection::make(), CarbonImmutable::parse("2026-11-23 {$now}"));
}

it('measures a day against its schedule', function (array $times, string $now, string $status, array $flags): void {
    $day = dayWith(nineToFive(), $times, $now);

    expect($day['status'])->toBe($status)
        ->and($day['flags'])->toBe($flags);
})->with([
    'on time' => [[['09:00', '17:00']], '18:00', 'present', []],
    'in within the grace' => [[['09:05', '17:00']], '18:00', 'present', []],
    'in after the grace' => [[['09:06', '17:00']], '18:00', 'late', ['late']],
    'out before the end less grace' => [[['09:00', '16:54']], '18:00', 'left_early', ['left_early']],
    'out within the grace' => [[['09:00', '16:55']], '18:00', 'present', []],
    'late and out early' => [[['10:00', '15:00']], '18:00', 'late', ['late', 'left_early']],
    'out for lunch, still working' => [[['09:00', '12:00'], ['12:30', null]], '14:00', 'present', []],
    'not in, before the grace' => [[], '09:05', 'upcoming', []],
    'not in, after the grace' => [[], '09:30', 'late', ['late']],
    'not in all day' => [[], '17:00', 'absent', ['absent']],
    'well past the end' => [[['09:00', '18:00']], '19:00', 'present', ['overtime']],
]);

it('counts how late, worked time and overtime beyond the expected hours', function (): void {
    $late = dayWith(nineToFive(breakMinutes: 30), [['09:20', '12:00'], ['12:30', '17:00']], '18:00');
    $long = dayWith(nineToFive(breakMinutes: 30), [['08:00', '12:00'], ['12:30', '17:30']], '18:00');
    $slightlyLong = dayWith(nineToFive(), [['09:00', '17:04']], '18:00');

    expect($late['late_seconds'])->toBe(20 * 60)
        ->and($late['expected_seconds'])->toBe((int) (7.5 * 3600))
        ->and($late['worked_seconds'])->toBe(7 * 3600 + 10 * 60)
        ->and($late['overtime_seconds'])->toBe(0)
        ->and($long['overtime_seconds'])->toBe((int) (1.5 * 3600))
        ->and($long['flags'])->toBe(['overtime'])
        ->and($slightlyLong['overtime_seconds'])->toBe(4 * 60)
        ->and($slightlyLong['flags'])->toBe([]);
});

it('counts a scheduled break that wasn’t clocked as time worked', function (): void {
    $day = dayWith(nineToFive(breakMinutes: 30), [['09:00', '17:00']], '18:00');

    expect($day['expected_seconds'])->toBe((int) (7.5 * 3600))
        ->and($day['worked_seconds'])->toBe(8 * 3600)
        ->and($day['overtime_seconds'])->toBe(30 * 60)
        ->and($day['flags'])->toBe(['overtime']);
});

it('counts time worked on a holiday or day off as overtime', function (string $kind): void {
    $day = dayWith(new ExpectedDay($kind, graceMinutes: 5, holiday: $kind === ExpectedDay::HOLIDAY ? 'Christmas Day' : null), [['10:00', '12:00']], '18:00');

    expect($day['status'])->toBe($kind)
        ->and($day['expected_seconds'])->toBe(0)
        ->and($day['overtime_seconds'])->toBe(2 * 3600)
        ->and($day['flags'])->toBe(['overtime']);
})->with([ExpectedDay::HOLIDAY, ExpectedDay::DAY_OFF]);

it('measures nothing when the team has no schedules', function (): void {
    $worked = dayWith(new ExpectedDay(ExpectedDay::UNSCHEDULED), [['10:00', '22:00']], '23:00');
    $idle = dayWith(new ExpectedDay(ExpectedDay::UNSCHEDULED), [], '23:00');

    expect($worked['status'])->toBe('unscheduled')
        ->and($worked['worked_seconds'])->toBe(12 * 3600)
        ->and($worked['overtime_seconds'])->toBe(0)
        ->and($worked['flags'])->toBe([])
        ->and($idle['status'])->toBe('unscheduled')
        ->and($idle['flags'])->toBe([]);
});
