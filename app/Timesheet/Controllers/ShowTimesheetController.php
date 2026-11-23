<?php

declare(strict_types=1);

namespace App\Timesheet\Controllers;

use App\Attendance\AttendanceCalendar;
use App\Attendance\AttendanceTimezone;
use App\Team\Exceptions\CouldNotViewTeam;
use App\Timesheet\Actions\FindTimesheet;
use App\Timesheet\Exceptions\CouldNotFindTimesheet;
use App\Timesheet\TimesheetAccess;
use App\Timesheet\TimesheetPeriods;
use App\Timesheet\TimesheetStatusEnum;
use App\Timesheet\TimesheetSummary;
use App\Timesheet\TimesheetTotals;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * One member's timesheet for one pay period: each day against their
 * schedule, with what the period adds up to. Members see their own; owners
 * and admins see anyone's, and approve or send it back here.
 */
final class ShowTimesheetController
{
    /**
     * @throws CouldNotViewTeam
     * @throws CouldNotFindTimesheet
     */
    public function __invoke(Request $request, string $member, string $startsOn): Response
    {
        $viewerId = (string) $request->user()?->id;
        $team = TimesheetAccess::team($request);
        $teamId = $team->getId()->toString();
        $canManage = Gate::allows('manageAttendance', $team);
        $periods = TimesheetPeriods::forTeam($team);
        $members = AttendanceTimezone::membersOfTeam($teamId);
        $person = $members->firstWhere('id', $member);

        if ($person === null || ($member !== $viewerId && ! $canManage) || ! $periods->startsOn($startsOn)) {
            throw CouldNotFindTimesheet::notFound();
        }

        $period = $periods->containing($startsOn);
        $calendar = AttendanceCalendar::forTeam($teamId, $members, $period['starts_on'], $period['ends_on']);
        $timesheet = FindTimesheet::forMemberStartingOn($teamId, $member, $period['starts_on']);
        $names = $members->mapWithKeys(fn (array $item): array => [$item['id'] => $item['name']])->all();
        $totals = TimesheetTotals::of($timesheet, $teamId, $member, $period['starts_on'], $period['ends_on'], $calendar);
        $today = CarbonImmutable::now()->setTimezone($person['timezone'])->toDateString();
        $summary = TimesheetSummary::toArray($timesheet, $teamId, $member, $period, $calendar, $names);

        return Inertia::render('attendance/timesheet', [
            'member' => ['id' => $person['id'], 'name' => $person['name'], 'timezone' => $person['timezone']],
            'isYou' => $member === $viewerId,
            'timesheet' => [
                ...$summary,
                'previous' => $periods->before($period['starts_on'])['starts_on'],
                'next' => $periods->after($period['starts_on'])['starts_on'],
                'is_kept' => $timesheet->getStatus()->toNullableEnum() === TimesheetStatusEnum::Approved,
            ],
            'days' => is_array($totals['days'] ?? null) ? array_values($totals['days']) : [],
            'canManage' => $canManage,
            'canSubmit' => $member === $viewerId && $period['starts_on'] <= $today && in_array($summary['status'], ['open', 'rejected'], true),
        ]);
    }
}
