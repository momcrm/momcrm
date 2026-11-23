<?php

declare(strict_types=1);

namespace App\Attendance\Controllers;

use App\Attendance\AttendanceCalendar;
use App\Attendance\AttendanceTimezone;
use App\Attendance\AttendanceWeek;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use App\Timesheet\Actions\FindTimesheet;
use App\Timesheet\TimesheetPeriods;
use App\Timesheet\TimesheetStatusEnum;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * A member's week of shifts and breaks, each day measured against their
 * schedule. Everyone sees their own, and can add a shift they forgot while
 * its timesheet is open; owners and admins can pick anyone in the team and
 * correct their shifts until the timesheet is approved.
 */
final class ShowAttendanceTimeController
{
    /**
     * @throws CouldNotViewTeam
     */
    public function __invoke(Request $request): Response
    {
        $viewerId = (string) $request->user()?->id;
        $team = FindTeam::byIdForMember($request->user()?->current_team_id, $viewerId);

        if ($team->isNull()) {
            throw CouldNotViewTeam::notFound();
        }

        $teamId = $team->getId()->toString();
        $canManage = Gate::allows('manageAttendance', $team);
        $members = AttendanceTimezone::membersOfTeam($teamId);
        $requested = $request->query('member');
        $memberId = is_string($requested) && $requested !== '' ? $requested : $viewerId;

        if ($memberId !== $viewerId) {
            Gate::authorize('manageAttendance', $team);
        }

        $member = $members->firstWhere('id', $memberId);

        if ($member === null) {
            throw CouldNotViewTeam::notFound();
        }

        $now = CarbonImmutable::now();
        $timezone = AttendanceTimezone::forUserId($memberId);
        $monday = AttendanceWeek::startOf($this->day($request->query('week'), $now, $timezone));
        $calendar = AttendanceCalendar::forTeam($teamId, $members, $monday->toDateString(), $monday->addDays(6)->toDateString());
        $schedule = $calendar->scheduleFor($memberId);
        $timesheet = FindTimesheet::coveringForMember($teamId, $memberId, $monday->toDateString());
        $timesheetStatus = $timesheet->getStatus()->toNullableEnum();
        $period = TimesheetPeriods::forTeam($team)->containing($monday->toDateString());

        return Inertia::render('attendance/time', [
            'member' => $member,
            'isYou' => $memberId === $viewerId,
            'week' => AttendanceWeek::forMember($teamId, $memberId, $monday, $timezone, $calendar, $now),
            'schedule' => $schedule->isNull() ? null : $schedule->toSummary(),
            'members' => $canManage ? $members->values()->all() : [],
            'canManage' => $canManage,
            'timesheet' => ['starts_on' => $period['starts_on'], 'status' => $timesheetStatus->value ?? 'open'],
            'isLocked' => $timesheetStatus === TimesheetStatusEnum::Approved,
            'canAddOwn' => ! $canManage && $memberId === $viewerId && $timesheetStatus?->locksMember() !== true,
        ]);
    }

    /**
     * The day asked for, or today in the member's timezone.
     */
    private function day(mixed $week, CarbonImmutable $now, string $timezone): CarbonImmutable
    {
        if (is_string($week) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $week) === 1) {
            try {
                return CarbonImmutable::createFromFormat('!Y-m-d', $week) ?? $now;
            } catch (Throwable) {
                // An impossible date falls back to this week.
            }
        }

        return CarbonImmutable::parse($now->setTimezone($timezone)->toDateString());
    }
}
