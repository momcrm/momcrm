<?php

declare(strict_types=1);

namespace App\Attendance\Controllers;

use App\Attendance\AttendanceCalendar;
use App\Attendance\AttendanceTimezone;
use App\Attendance\LeaveSummary;
use App\Choice\Actions\FindChoice;
use App\Choice\Choice;
use App\Choice\ChoiceListEnum;
use App\LeaveRequest\Actions\FindLeaveRequest;
use App\LeaveRequest\LeaveRequest;
use App\LeaveRequest\LeaveRequestCollection;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Leave tab: the viewer's own requests and a form to ask for more. Owners
 * and admins also get the requests waiting for them, the team's approved
 * leave coming up, and can record leave for anyone.
 */
final class ShowAttendanceLeaveController
{
    public const int UPCOMING_DAYS = 90;

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
        $timezones = $members->mapWithKeys(fn (array $member): array => [$member['id'] => $member['timezone']])->all();
        $today = CarbonImmutable::now()->setTimezone(AttendanceTimezone::forUserId($viewerId))->toDateString();

        $mine = FindLeaveRequest::forMember($teamId, $viewerId);
        $pending = $canManage ? FindLeaveRequest::pendingForTeam($teamId) : new LeaveRequestCollection;
        $upcoming = $canManage
            ? FindLeaveRequest::approvedBetweenForTeam($teamId, $today, CarbonImmutable::parse($today)->addDays(self::UPCOMING_DAYS)->toDateString())
            : new LeaveRequestCollection;

        $all = $mine->merge($pending)->merge($upcoming);
        $from = (string) ($all->min(fn (LeaveRequest $leave): string => $leave->getStartsOn()->toDateString()) ?? $today);
        $to = (string) ($all->max(fn (LeaveRequest $leave): string => $leave->getEndsOn()->toDateString()) ?? $today);
        $calendar = AttendanceCalendar::forTeam($teamId, $members, $from, $to);

        $names = $members->mapWithKeys(fn (array $member): array => [$member['id'] => $member['name']])->all();
        $types = FindChoice::listForTeam($teamId, ChoiceListEnum::LeaveType, withArchived: true)
            ->mapWithKeys(fn (Choice $type): array => [$type->getId()->toString() => ['name' => $type->getName()->toString(), 'hue' => $type->getHue()->toNullableInteger()]])
            ->all();
        $summaries = fn (LeaveRequestCollection $leave): array => array_values($leave
            ->map(fn (LeaveRequest $item): array => LeaveSummary::toArray($item, $names, $types, $calendar, $timezones[$item->getUserId()->toString()] ?? AttendanceTimezone::orDefault(null), $viewerId, $canManage))
            ->all());

        return Inertia::render('attendance/leave', [
            'today' => $today,
            'mine' => $summaries($mine),
            'pending' => $summaries($pending),
            'upcoming' => $summaries($upcoming),
            'members' => $canManage ? array_values($members->map(fn (array $member): array => ['id' => $member['id'], 'name' => $member['name']])->all()) : [],
            'canManage' => $canManage,
        ]);
    }
}
