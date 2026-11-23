<?php

declare(strict_types=1);

namespace App\ShiftBreak;

use App\Shared\Abstracts\AbstractDataCollection;
use Carbon\CarbonImmutable;

/**
 * @extends AbstractDataCollection<int, ShiftBreak>
 */
final class ShiftBreakCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return ShiftBreak::class;
    }

    /**
     * The breaks taken during one shift, earliest first.
     */
    public function forShift(string $shiftId): self
    {
        return $this
            ->filter(fn (ShiftBreak $break): bool => $break->getShiftId()->toNullableString() === $shiftId)
            ->sortBy(fn (ShiftBreak $break): int => $break->getStartedAt()->toCarbon()->getTimestamp())
            ->values();
    }

    /**
     * The break still running, or an empty one.
     */
    public function open(): ShiftBreak
    {
        return $this->first(fn (ShiftBreak $break): bool => $break->isOpen());
    }

    /**
     * Seconds spent on these breaks, counting an open one up to now.
     */
    public function secondsUntil(CarbonImmutable $now): int
    {
        return (int) $this->sum(fn (ShiftBreak $break): int => max(0, (int) $break->getStartedAt()->toCarbon()->diffInSeconds(
            $break->getEndedAt()->toNullableCarbon() ?? $now,
        )));
    }
}
