<?php

declare(strict_types=1);

namespace App\Attendance;

use App\Holiday\Actions\FindHoliday;
use App\Holiday\HolidayCollection;
use App\WorkSchedule\Actions\FindWorkSchedule;
use App\WorkSchedule\WorkSchedule;
use App\WorkSchedule\WorkScheduleCollection;
use App\WorkSchedule\WorkScheduleDays;
use Carbon\CarbonImmutable;

/**
 * What a team expects of each member on each day: the hours of their own
 * schedule or the team's default, less the team's holidays. Load it once for
 * a range of days, then ask it about any member and day in that range.
 */
final readonly class AttendanceCalendar
{
    /**
     * @param  array<string, ?string>  $assignments  member id => their own schedule's id, if any
     */
    public function __construct(
        private WorkScheduleCollection $schedules,
        private HolidayCollection $holidays,
        private array $assignments,
    ) {}

    /**
     * @param  iterable<array{id: string, work_schedule_id: ?string}>  $members  from AttendanceTimezone::membersOfTeam()
     */
    public static function forTeam(string $teamId, iterable $members, string $from, string $to): self
    {
        $assignments = [];

        foreach ($members as $member) {
            $assignments[$member['id']] = $member['work_schedule_id'];
        }

        return new self(
            FindWorkSchedule::listForTeam($teamId),
            FindHoliday::betweenForTeam($teamId, $from, $to),
            $assignments,
        );
    }

    /**
     * The member's own schedule, else the team's default, else an empty one.
     */
    public function scheduleFor(string $userId): WorkSchedule
    {
        $own = $this->schedules->findById($this->assignments[$userId] ?? null);

        return $own->isNotNull() ? $own : $this->schedules->defaultSchedule();
    }

    /**
     * What the member is expected to work on this date (YYYY-MM-DD), with
     * times in their timezone.
     */
    public function expectedOn(string $userId, string $date, string $timezone): ExpectedDay
    {
        $schedule = $this->scheduleFor($userId);

        if ($schedule->isNull()) {
            return new ExpectedDay(ExpectedDay::UNSCHEDULED);
        }

        $graceMinutes = $schedule->getGraceMinutes()->toInteger();
        $holiday = $this->holidays->nameOn($date);

        if ($holiday !== null) {
            return new ExpectedDay(ExpectedDay::HOLIDAY, graceMinutes: $graceMinutes, holiday: $holiday);
        }

        $day = WorkScheduleDays::forDate($schedule->getDays()->toDays(), CarbonImmutable::parse($date));

        if ($day === null) {
            return new ExpectedDay(ExpectedDay::DAY_OFF, graceMinutes: $graceMinutes);
        }

        $startsAt = CarbonImmutable::parse("{$date} {$day['starts_at']}", $timezone);
        $endsOn = $day['ends_at'] > $day['starts_at'] ? $date : CarbonImmutable::parse($date)->addDay()->toDateString();

        return new ExpectedDay(
            ExpectedDay::WORKDAY,
            startsAt: $startsAt,
            endsAt: CarbonImmutable::parse("{$endsOn} {$day['ends_at']}", $timezone),
            breakMinutes: $day['break_minutes'],
            graceMinutes: $graceMinutes,
        );
    }
}
