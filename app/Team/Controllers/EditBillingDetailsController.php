<?php

declare(strict_types=1);

namespace App\Team\Controllers;

use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The business details printed on the team's invoices, for owners and admins to edit.
 */
final class EditBillingDetailsController
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

        Gate::authorize('manageBilling', $team);

        return Inertia::render('settings/billing', [
            'teamName' => $team->getName()->toString(),
            'billing' => $team->toBillingSummary(),
        ]);
    }
}
