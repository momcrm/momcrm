<?php

declare(strict_types=1);

namespace App\Attendance;

use App\LeaveRequest\Actions\FindLeaveRequest;
use App\LeaveRequest\LeaveRequest;
use App\Timesheet\Actions\FindTimesheet;
use Carbon\CarbonImmutable;

/**
 * Attendance in brief for a member's profile: their hours this week against
 * their schedule, the working days of approved leave starting this year, and
 * their latest timesheet.
 */
final class MemberAttendanceSummary
{
    /**
     * @return array{week_worked_seconds: int, week_expected_seconds: int, leave_days_this_year: float, last_timesheet: ?array{starts_on: string, ends_on: string, status: string}}
     */
    public static function forMember(string $teamId, string $userId, CarbonImmutable $now): array
    {
        $members = AttendanceTimezone::membersOfTeam($teamId);
        $timezone = AttendanceTimezone::forUserId($userId);
        $today = $now->setTimezone($timezone);
        $monday = AttendanceWeek::startOf(CarbonImmutable::parse($today->toDateString()));
        $yearStart = $today->startOfYear()->toDateString();
        $yearEnd = $today->endOfYear()->toDateString();
        $calendar = AttendanceCalendar::forTeam($teamId, $members, min($yearStart, $monday->toDateString()), max($yearEnd, $monday->addDays(6)->toDateString()));
        $week = AttendanceWeek::forMember($teamId, $userId, $monday, $timezone, $calendar, $now);
        $timesheet = FindTimesheet::latestForMember($teamId, $userId);

        return [
            'week_worked_seconds' => $week['worked_seconds'],
            'week_expected_seconds' => $week['expected_seconds'],
            'leave_days_this_year' => (float) FindLeaveRequest::approvedBetweenForTeam($teamId, $yearStart, $yearEnd)
                ->filter(fn (LeaveRequest $leave): bool => $leave->getUserId()->toNullableString() === $userId && $leave->getStartsOn()->toDateString() >= $yearStart)
                ->sum(fn (LeaveRequest $leave): float => $calendar->workdaysOf($leave, $timezone) ?? 0.0),
            'last_timesheet' => $timesheet->isNull() ? null : [
                'starts_on' => $timesheet->getStartsOn()->toDateString(),
                'ends_on' => $timesheet->getEndsOn()->toDateString(),
                'status' => $timesheet->getStatus()->toEnum()->value,
            ],
        ];
    }
}
