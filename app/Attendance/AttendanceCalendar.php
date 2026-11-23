<?php

declare(strict_types=1);

namespace App\Attendance;

use App\Choice\Actions\FindChoice;
use App\Choice\Choice;
use App\Choice\ChoiceListEnum;
use App\Holiday\Actions\FindHoliday;
use App\Holiday\HolidayCollection;
use App\LeaveRequest\Actions\FindLeaveRequest;
use App\LeaveRequest\LeavePartEnum;
use App\LeaveRequest\LeaveRequest;
use App\LeaveRequest\LeaveRequestCollection;
use App\WorkSchedule\Actions\FindWorkSchedule;
use App\WorkSchedule\WorkSchedule;
use App\WorkSchedule\WorkScheduleCollection;
use App\WorkSchedule\WorkScheduleDays;
use Carbon\CarbonImmutable;

/**
 * What a team expects of each member on each day: the hours of their own
 * schedule or the team's default, less the team's holidays and their approved
 * leave. Load it once for a range of days, then ask it about any member and
 * day in that range.
 */
final readonly class AttendanceCalendar
{
    /**
     * @param  array<string, ?string>  $assignments  member id => their own schedule's id, if any
     * @param  array<string, string>  $leaveTypes  leave type id => name
     */
    public function __construct(
        private WorkScheduleCollection $schedules,
        private HolidayCollection $holidays,
        private array $assignments,
        private LeaveRequestCollection $leave = new LeaveRequestCollection,
        private array $leaveTypes = [],
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
            FindLeaveRequest::approvedBetweenForTeam($teamId, $from, $to),
            FindChoice::listForTeam($teamId, ChoiceListEnum::LeaveType, withArchived: true)
                ->mapWithKeys(fn (Choice $type): array => [$type->getId()->toString() => $type->getName()->toString()])
                ->all(),
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
     * times in their timezone, after their approved leave.
     */
    public function expectedOn(string $userId, string $date, string $timezone): ExpectedDay
    {
        $scheduled = $this->scheduledOn($userId, $date, $timezone);
        $leave = $this->leave->forMemberOn($userId, $date);

        if ($leave->isNull() || in_array($scheduled->kind, [ExpectedDay::HOLIDAY, ExpectedDay::DAY_OFF], true)) {
            return $scheduled;
        }

        $name = $this->leaveTypeName($leave);
        $part = $leave->getPart()->toEnum();

        if (! $part->isHalfDay()) {
            return new ExpectedDay(ExpectedDay::ON_LEAVE, graceMinutes: $scheduled->graceMinutes, leave: $name);
        }

        $label = $name.' ('.mb_strtolower($part->label()).')';

        if (! $scheduled->isWorkday() || ! $scheduled->startsAt instanceof CarbonImmutable || ! $scheduled->endsAt instanceof CarbonImmutable) {
            return new ExpectedDay($scheduled->kind, leave: $label);
        }

        $midday = $scheduled->startsAt->addSeconds(intdiv((int) $scheduled->startsAt->diffInSeconds($scheduled->endsAt), 2));

        return new ExpectedDay(
            ExpectedDay::WORKDAY,
            startsAt: $part === LeavePartEnum::Morning ? $midday : $scheduled->startsAt,
            endsAt: $part === LeavePartEnum::Morning ? $scheduled->endsAt : $midday,
            graceMinutes: $scheduled->graceMinutes,
            leave: $label,
        );
    }

    /**
     * How many of the member's working days the leave takes, a half day
     * counting a half, or null when they have no schedule to count against.
     * Holidays and days off inside it don't count.
     */
    public function workdaysOf(LeaveRequest $leave, string $timezone): ?float
    {
        $userId = $leave->getUserId()->toString();

        if ($this->scheduleFor($userId)->isNull()) {
            return null;
        }

        $days = 0.0;
        $date = $leave->getStartsOn()->toCarbon();
        $last = $leave->getEndsOn()->toDateString();

        for (; $date->toDateString() <= $last; $date = $date->addDay()) {
            if ($this->scheduledOn($userId, $date->toDateString(), $timezone)->isWorkday()) {
                $days += $leave->getPart()->toEnum()->isHalfDay() ? 0.5 : 1.0;
            }
        }

        return $days;
    }

    /**
     * What the member's schedule and the team's holidays expect on this date,
     * before any leave.
     */
    private function scheduledOn(string $userId, string $date, string $timezone): ExpectedDay
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

    private function leaveTypeName(LeaveRequest $leave): string
    {
        return $this->leaveTypes[$leave->getLeaveTypeId()->toString()] ?? __('Leave');
    }
}
