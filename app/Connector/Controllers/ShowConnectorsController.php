<?php

declare(strict_types=1);

namespace App\Connector\Controllers;

use App\Connector\Actions\FindConnector;
use App\Connector\Connector;
use App\Connector\ConnectorTypeEnum;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The outside accounts the team has connected, and the kinds it can add, for
 * owners and admins to manage.
 */
final class ShowConnectorsController
{
    /**
     * @throws CouldNotViewTeam
     */
    public function __invoke(Request $request): Response
    {
        $team = FindTeam::byIdForMember(id: $request->user()?->current_team_id, userId: (string) $request->user()?->id);

        if ($team->isNull()) {
            throw CouldNotViewTeam::notFound();
        }

        Gate::authorize('manageConnectors', $team);

        return Inertia::render('settings/connectors', [
            'connectors' => FindConnector::allForTeam($team->getId()->toString())
                ->map(fn (Connector $connector): array => $connector->toListItem())
                ->values()
                ->all(),
            'types' => ConnectorTypeEnum::available(),
        ]);
    }
}
