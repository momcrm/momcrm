<?php

declare(strict_types=1);

namespace App\Attendance;

use App\Shift\Shift;
use App\ShiftBreak\ShiftBreak;
use App\ShiftBreak\ShiftBreakCollection;
use Carbon\CarbonImmutable;

/**
 * A shift with its breaks as the attendance pages show it. Times come both as
 * ISO instants and as wall-clock times in the member's timezone, which the
 * correction form edits.
 */
final class ShiftSummary
{
    public const string LOCAL_FORMAT = 'Y-m-d\TH:i';

    /**
     * @return array{id: string, user_id: string, work_date: string, clocked_in_at: string, clocked_out_at: ?string, clocked_in_local: string, clocked_out_local: ?string, note: ?string, is_open: bool, on_break_since: ?string, worked_seconds: int, break_seconds: int, breaks: list<array{id: string, started_at: string, ended_at: ?string, started_local: string, ended_local: ?string}>}
     */
    public static function toArray(Shift $shift, ShiftBreakCollection $breaks, string $timezone, CarbonImmutable $now): array
    {
        $breaks = $breaks->forShift($shift->getId()->toString());
        $clockedInAt = $shift->getClockedInAt()->toCarbon();
        $clockedOutAt = $shift->getClockedOutAt()->toNullableCarbon();

        return [
            'id' => $shift->getId()->toString(),
            'user_id' => $shift->getUserId()->toString(),
            'work_date' => $shift->getWorkDate()->toDateString(),
            'clocked_in_at' => $shift->getClockedInAt()->toISOString(),
            'clocked_out_at' => $shift->getClockedOutAt()->toNullableISOString(),
            'clocked_in_local' => self::local($clockedInAt, $timezone),
            'clocked_out_local' => $clockedOutAt === null ? null : self::local($clockedOutAt, $timezone),
            'note' => $shift->getNote()->toNullableString(),
            'is_open' => $shift->isOpen(),
            'on_break_since' => $breaks->open()->getStartedAt()->toNullableISOString(),
            'worked_seconds' => self::workedSeconds($shift, $breaks, $now),
            'break_seconds' => $breaks->secondsUntil($clockedOutAt ?? $now),
            'breaks' => array_values($breaks->map(fn (ShiftBreak $break): array => [
                'id' => $break->getId()->toString(),
                'started_at' => $break->getStartedAt()->toISOString(),
                'ended_at' => $break->getEndedAt()->toNullableISOString(),
                'started_local' => self::local($break->getStartedAt()->toCarbon(), $timezone),
                'ended_local' => $break->getEndedAt()->isNull() ? null : self::local($break->getEndedAt()->toCarbon(), $timezone),
            ])->all()),
        ];
    }

    /**
     * Time on the clock minus breaks, counting an open shift or break up to now.
     */
    public static function workedSeconds(Shift $shift, ShiftBreakCollection $breaksOfShift, CarbonImmutable $now): int
    {
        $end = $shift->getClockedOutAt()->toNullableCarbon() ?? $now;
        $onClock = max(0, (int) $shift->getClockedInAt()->toCarbon()->diffInSeconds($end));

        return max(0, $onClock - $breaksOfShift->secondsUntil($end));
    }

    private static function local(CarbonImmutable $instant, string $timezone): string
    {
        return $instant->setTimezone($timezone)->format(self::LOCAL_FORMAT);
    }
}
