<?php

declare(strict_types=1);

namespace App\Choice;

use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use App\Team\Team;
use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Who may edit the team's lists: owners and admins (TeamPolicy::manageWorkflow).
 */
final class ChoiceAccess
{
    /**
     * @throws CouldNotViewTeam
     */
    public static function authorize(Request $request): Response
    {
        return Gate::authorize('manageWorkflow', self::team($request));
    }

    /**
     * @throws CouldNotViewTeam
     */
    public static function team(Request $request): Team
    {
        $team = FindTeam::byIdForMember(id: $request->user()?->current_team_id, userId: (string) $request->user()?->id);

        if ($team->isNull()) {
            throw CouldNotViewTeam::notFound();
        }

        return $team;
    }
}
