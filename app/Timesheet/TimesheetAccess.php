<?php

declare(strict_types=1);

namespace App\Timesheet;

use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use App\Team\Team;
use Illuminate\Http\Request;

/**
 * The viewer's current team, which every timesheet page and change starts
 * from. Who may do what is decided by TeamPolicy::manageAttendance.
 */
final class TimesheetAccess
{
    /**
     * @throws CouldNotViewTeam
     */
    public static function team(Request $request): Team
    {
        $team = FindTeam::byIdForMember($request->user()?->current_team_id, (string) $request->user()?->id);

        if ($team->isNull()) {
            throw CouldNotViewTeam::notFound();
        }

        return $team;
    }
}
