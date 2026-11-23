<?php

declare(strict_types=1);

namespace App\Attendance;

use Carbon\CarbonImmutable;

/**
 * What a member is expected to work on one day: their schedule's hours as
 * instants in their timezone, or a holiday, day off or approved leave. Half a
 * day's leave leaves the other half as a shorter workday. A team with no
 * schedules expects nothing, so its days are unscheduled and never flagged.
 */
final readonly class ExpectedDay
{
    public const string WORKDAY = 'workday';

    public const string DAY_OFF = 'day_off';

    public const string HOLIDAY = 'holiday';

    public const string ON_LEAVE = 'on_leave';

    public const string UNSCHEDULED = 'unscheduled';

    public function __construct(
        public string $kind,
        public ?CarbonImmutable $startsAt = null,
        public ?CarbonImmutable $endsAt = null,
        public int $breakMinutes = 0,
        public int $graceMinutes = 0,
        public ?string $holiday = null,
        public ?string $leave = null,
    ) {}

    public function isWorkday(): bool
    {
        return $this->kind === self::WORKDAY && $this->startsAt instanceof CarbonImmutable && $this->endsAt instanceof CarbonImmutable;
    }

    /**
     * Seconds of work expected: from start to end less the break.
     */
    public function expectedSeconds(): int
    {
        if (! $this->isWorkday()) {
            return 0;
        }

        return max(0, (int) $this->startsAt?->diffInSeconds($this->endsAt) - $this->breakMinutes * 60);
    }

    public function graceSeconds(): int
    {
        return $this->graceMinutes * 60;
    }
}
