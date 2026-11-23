<?php

declare(strict_types=1);

namespace App\Attendance\Controllers;

use App\Attendance\AttendanceBoard;
use App\Attendance\AttendanceTimezone;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use App\User\Actions\FindUser;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Who is working today, by the viewer's own day: who is in, on a break,
 * finished or not in yet.
 */
final class ShowAttendanceController
{
    /**
     * @throws CouldNotViewTeam
     */
    public function __invoke(Request $request): Response
    {
        $userId = (string) $request->user()?->id;
        $team = FindTeam::byIdForMember($request->user()?->current_team_id, $userId);

        if ($team->isNull()) {
            throw CouldNotViewTeam::notFound();
        }

        $now = CarbonImmutable::now();
        $today = $now->setTimezone(AttendanceTimezone::forUser(FindUser::byId($userId)))->toDateString();

        return Inertia::render('attendance/today', [
            'today' => $today,
            'members' => AttendanceBoard::forTeam($team->getId()->toString(), $today, $now),
            'canManage' => Gate::allows('manageAttendance', $team),
        ]);
    }
}
