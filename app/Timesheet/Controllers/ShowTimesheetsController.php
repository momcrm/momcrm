<?php

declare(strict_types=1);

namespace App\Timesheet\Controllers;

use App\Attendance\AttendanceCalendar;
use App\Attendance\AttendanceTimezone;
use App\Team\Exceptions\CouldNotViewTeam;
use App\Timesheet\Actions\FindTimesheet;
use App\Timesheet\TimesheetAccess;
use App\Timesheet\TimesheetPeriods;
use App\Timesheet\TimesheetSummary;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Timesheets tab: the viewer's recent pay periods, to submit. Owners and
 * admins also get everyone's timesheets for one period (the last finished
 * one unless `?period=` picks another) to approve and export.
 */
final class ShowTimesheetsController
{
    public const int OWN_PERIODS = 6;

    /**
     * @throws CouldNotViewTeam
     */
    public function __invoke(Request $request): Response
    {
        $viewerId = (string) $request->user()?->id;
        $team = TimesheetAccess::team($request);
        $teamId = $team->getId()->toString();
        $canManage = Gate::allows('manageAttendance', $team);
        $periods = TimesheetPeriods::forTeam($team);
        $members = AttendanceTimezone::membersOfTeam($teamId);
        $names = $members->mapWithKeys(fn (array $member): array => [$member['id'] => $member['name']])->all();
        $today = CarbonImmutable::now()->setTimezone(AttendanceTimezone::forUserId($viewerId))->toDateString();

        $recent = $periods->recent($today, self::OWN_PERIODS);
        $oldest = $recent[count($recent) - 1];
        $requested = $request->string('period')->toString();
        $teamPeriod = $periods->startsOn($requested) ? $periods->containing($requested) : $periods->before($recent[0]['starts_on']);
        $from = min($oldest['starts_on'], $teamPeriod['starts_on']);
        $to = max($recent[0]['ends_on'], $teamPeriod['ends_on']);
        $calendar = AttendanceCalendar::forTeam($teamId, $members, $from, $to);
        $timesheets = FindTimesheet::startingBetweenForTeam($teamId, $from, $to);

        return Inertia::render('attendance/timesheets', [
            'today' => $today,
            'periodLength' => $periods->period->value,
            'mine' => array_map(fn (array $period): array => [
                ...TimesheetSummary::toArray($timesheets->forMember($viewerId, $period['starts_on']), $teamId, $viewerId, $period, $calendar, $names),
                'can_submit' => $period['starts_on'] <= $today && in_array($timesheets->forMember($viewerId, $period['starts_on'])->getStatus()->toNullableString() ?? 'open', ['open', 'rejected'], true),
            ], $recent),
            'team' => $canManage ? [
                'period' => [
                    ...$teamPeriod,
                    'previous' => $periods->before($teamPeriod['starts_on'])['starts_on'],
                    'next' => $periods->after($teamPeriod['starts_on'])['starts_on'],
                ],
                'rows' => array_values($members->map(fn (array $member): array => [
                    'user_id' => $member['id'],
                    'name' => $member['name'],
                    ...TimesheetSummary::toArray($timesheets->forMember($member['id'], $teamPeriod['starts_on']), $teamId, $member['id'], $teamPeriod, $calendar, $names),
                ])->all()),
            ] : null,
            'canManage' => $canManage,
        ]);
    }
}
