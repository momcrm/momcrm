<?php

declare(strict_types=1);

namespace App\Attendance;

use App\Shift\Actions\FindShift;
use App\Shift\Shift;
use App\ShiftBreak\Actions\FindShiftBreak;
use Carbon\CarbonImmutable;

/**
 * Who is working today: each member with whether they are in, on a break,
 * finished, not in yet or off, and their hours so far. Each member's today
 * is the date in their own timezone.
 *
 * How the day is going against their schedule (late, absent, overtime) is
 * only given to those allowed to see it: owners and admins for everyone,
 * members for themselves.
 */
final class AttendanceBoard
{
    public const string IN = 'in';

    public const string ON_BREAK = 'break';

    public const string OUT = 'out';

    public const string NOT_IN = 'not_in';

    public const string OFF = 'off';

    /**
     * @return list<array{id: string, name: string, timezone: string, work_schedule_id: ?string, work_date: string, status: string, first_in_at: ?string, clocked_in_at: ?string, last_out_at: ?string, on_break_since: ?string, worked_seconds: int, day: ?array<string, mixed>}>
     */
    public static function forTeam(string $teamId, CarbonImmutable $now, string $viewerId, bool $canSeeEveryone): array
    {
        $members = AttendanceTimezone::membersOfTeam($teamId)
            ->map(fn (array $member): array => [...$member, 'work_date' => $now->setTimezone($member['timezone'])->toDateString()]);
        $dates = [$now->toDateString(), ...$members->map(fn (array $member): string => $member['work_date'])->all()];
        $from = min($dates);
        $to = max($dates);
        $calendar = AttendanceCalendar::forTeam($teamId, $members, $from, $to);
        $shifts = FindShift::forTeamBetweenOrOpen($teamId, $from, $to);
        $breaks = FindShiftBreak::forShifts($shifts->ids());

        return array_values($members
            ->map(function (array $member) use ($shifts, $breaks, $calendar, $now, $viewerId, $canSeeEveryone): array {
                $own = $shifts
                    ->filter(fn (Shift $shift): bool => $shift->getUserId()->toNullableString() === $member['id']
                        && ($shift->isOpen() || $shift->getWorkDate()->toDateString() === $member['work_date']))
                    ->values();
                $open = $own->first(fn (Shift $shift): bool => $shift->isOpen());
                $openBreak = $open->isNull() ? null : $breaks->forShift($open->getId()->toString())->open();
                $last = $own->sortByDesc(fn (Shift $shift): int => $shift->getClockedOutAt()->toNullableCarbon()?->getTimestamp() ?? PHP_INT_MAX)->first();
                $expected = $calendar->expectedOn($member['id'], $member['work_date'], $member['timezone']);
                $day = AttendanceDay::calculate($expected, $own, $breaks, $now);

                return [
                    ...$member,
                    'status' => match (true) {
                        $openBreak?->isNotNull() === true => self::ON_BREAK,
                        $open->isNotNull() => self::IN,
                        $own->isNotEmpty() => self::OUT,
                        in_array($expected->kind, [ExpectedDay::DAY_OFF, ExpectedDay::HOLIDAY], true) => self::OFF,
                        default => self::NOT_IN,
                    },
                    'first_in_at' => $own->first()->getClockedInAt()->toNullableISOString(),
                    'clocked_in_at' => $open->getClockedInAt()->toNullableISOString(),
                    'last_out_at' => $open->isNull() ? $last->getClockedOutAt()->toNullableISOString() : null,
                    'on_break_since' => $openBreak?->getStartedAt()->toNullableISOString(),
                    'worked_seconds' => $day['worked_seconds'],
                    'day' => $canSeeEveryone || $member['id'] === $viewerId ? $day : null,
                ];
            })
            ->all());
    }
}
