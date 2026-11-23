<?php

declare(strict_types=1);

namespace App\Attendance;

use App\Shift\Actions\FindShift;
use App\Shift\Shift;
use App\ShiftBreak\Actions\FindShiftBreak;
use Carbon\CarbonImmutable;

/**
 * Who is working today: each member with whether they are in, on a break,
 * finished for the day or not in yet, and their hours so far.
 */
final class AttendanceBoard
{
    public const string IN = 'in';

    public const string ON_BREAK = 'break';

    public const string OUT = 'out';

    public const string NOT_IN = 'not_in';

    /**
     * @return list<array{id: string, name: string, timezone: string, status: string, first_in_at: ?string, clocked_in_at: ?string, last_out_at: ?string, on_break_since: ?string, worked_seconds: int}>
     */
    public static function forTeam(string $teamId, string $workDate, CarbonImmutable $now): array
    {
        $shifts = FindShift::forTeamOnDay($teamId, $workDate);
        $breaks = FindShiftBreak::forShifts($shifts->ids());

        return array_values(AttendanceTimezone::membersOfTeam($teamId)
            ->map(function (array $member) use ($shifts, $breaks, $now): array {
                $own = $shifts->filter(fn (Shift $shift): bool => $shift->getUserId()->toNullableString() === $member['id'])->values();
                $open = $own->first(fn (Shift $shift): bool => $shift->isOpen());
                $openBreak = $open->isNull() ? null : $breaks->forShift($open->getId()->toString())->open();
                $last = $own->sortByDesc(fn (Shift $shift): int => $shift->getClockedOutAt()->toNullableCarbon()?->getTimestamp() ?? PHP_INT_MAX)->first();

                return [
                    ...$member,
                    'status' => match (true) {
                        $openBreak?->isNotNull() === true => self::ON_BREAK,
                        $open->isNotNull() => self::IN,
                        $own->isNotEmpty() => self::OUT,
                        default => self::NOT_IN,
                    },
                    'first_in_at' => $own->first()->getClockedInAt()->toNullableISOString(),
                    'clocked_in_at' => $open->getClockedInAt()->toNullableISOString(),
                    'last_out_at' => $open->isNull() ? $last->getClockedOutAt()->toNullableISOString() : null,
                    'on_break_since' => $openBreak?->getStartedAt()->toNullableISOString(),
                    'worked_seconds' => (int) $own->sum(fn (Shift $shift): int => ShiftSummary::workedSeconds($shift, $breaks->forShift($shift->getId()->toString()), $now)),
                ];
            })
            ->all());
    }
}
