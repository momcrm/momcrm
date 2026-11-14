<?php

declare(strict_types=1);

namespace App\Team\Controllers;

use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class EditTeamController
{
    /**
     * @throws CouldNotViewTeam
     */
    public function __invoke(Request $request): Response
    {
        $team = FindTeam::byIdForMember(
            id: $request->user()?->current_team_id,
            userId: (string) $request->user()?->getAuthIdentifier(),
        );

        if ($team->isNull()) {
            throw CouldNotViewTeam::notFound();
        }

        return Inertia::render('settings/team', [
            'team' => $team->toSummary(),
            'canUpdateTeam' => Gate::allows('update', $team),
        ]);
    }
}
