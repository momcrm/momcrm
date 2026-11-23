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
 * Who is working today: who is in, on a break, finished, not in yet or off.
 * Owners and admins also see who is late or absent.
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

        $canManage = Gate::allows('manageAttendance', $team);

        return Inertia::render('attendance/today', [
            'today' => $today,
            'members' => AttendanceBoard::forTeam($team->getId()->toString(), $now, $userId, $canManage),
            'canManage' => $canManage,
        ]);
    }
}
