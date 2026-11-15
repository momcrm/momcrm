<?php

declare(strict_types=1);

namespace App\Onboarding\Controllers;

use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShowWorkspaceController
{
    /**
     * @throws CouldNotViewTeam
     */
    public function __invoke(Request $request): Response
    {
        $team = FindTeam::byIdForMember(
            id: $request->user()?->current_team_id,
            userId: (string) $request->user()?->id,
        );

        if ($team->isNull()) {
            throw CouldNotViewTeam::notFound();
        }

        return Inertia::render('onboarding/workspace', [
            'team' => $team->toSummary(),
        ]);
    }
}
