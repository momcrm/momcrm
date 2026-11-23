<?php

declare(strict_types=1);

namespace App\Attendance\Controllers;

use App\Attendance\AttendanceAccess;
use App\Attendance\AttendanceTimezone;
use App\Holiday\Actions\FindHoliday;
use App\Holiday\Holiday;
use App\Team\Exceptions\CouldNotViewTeam;
use App\WorkSchedule\Actions\FindWorkSchedule;
use App\WorkSchedule\WorkSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Settings → Attendance: the team's schedules, who works to which, and its
 * holidays from the start of last year on. Owners and admins only.
 */
final class ShowAttendanceSettingsController
{
    /**
     * @throws CouldNotViewTeam
     */
    public function __invoke(Request $request): Response
    {
        AttendanceAccess::authorize($request);
        $teamId = AttendanceAccess::team($request)->getId()->toString();

        return Inertia::render('settings/attendance', [
            'schedules' => array_values(FindWorkSchedule::listForTeam($teamId)->map(fn (WorkSchedule $schedule): array => $schedule->toSummary())->all()),
            'members' => array_values(AttendanceTimezone::membersOfTeam($teamId)
                ->map(fn (array $member): array => ['id' => $member['id'], 'name' => $member['name'], 'work_schedule_id' => $member['work_schedule_id']])
                ->all()),
            'holidays' => array_values(FindHoliday::fromDateForTeam($teamId, CarbonImmutable::now()->subYear()->startOfYear()->toDateString())
                ->map(fn (Holiday $holiday): array => $holiday->toSummary())
                ->all()),
        ]);
    }
}
