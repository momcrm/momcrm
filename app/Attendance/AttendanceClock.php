<?php

declare(strict_types=1);

namespace App\Attendance;

use App\Shift\Actions\FindShift;
use App\ShiftBreak\Actions\FindShiftBreak;
use App\ShiftBreak\ShiftBreak;
use Carbon\CarbonImmutable;

/**
 * What the clock in the app's header shows for the signed-in member: whether
 * they are clocked in or on a break, and since when. The browser keeps the
 * timers ticking from these.
 */
final class AttendanceClock
{
    /**
     * @return array{shift_id: ?string, clocked_in_at: ?string, on_break_since: ?string, break_seconds: int}
     */
    public static function forMember(string $teamId, string $userId): array
    {
        $shift = FindShift::openForMember($teamId, $userId);

        if ($shift->isNull()) {
            return ['shift_id' => null, 'clocked_in_at' => null, 'on_break_since' => null, 'break_seconds' => 0];
        }

        $breaks = FindShiftBreak::forShifts([$shift->getId()->toString()]);
        $finished = $breaks->filter(fn (ShiftBreak $break): bool => ! $break->isOpen());

        return [
            'shift_id' => $shift->getId()->toString(),
            'clocked_in_at' => $shift->getClockedInAt()->toISOString(),
            'on_break_since' => $breaks->open()->getStartedAt()->toNullableISOString(),
            'break_seconds' => $finished->secondsUntil(CarbonImmutable::now()),
        ];
    }
}
