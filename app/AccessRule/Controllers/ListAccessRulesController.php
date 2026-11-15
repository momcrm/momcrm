<?php

declare(strict_types=1);

namespace App\AccessRule\Controllers;

use App\AccessRule\AccessRule;
use App\AccessRule\AccessRuleFormOptions;
use App\AccessRule\Actions\FindAccessRules;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The team's access rules, for owners and admins to manage.
 */
final class ListAccessRulesController
{
    /**
     * @throws CouldNotViewTeam
     */
    public function __invoke(Request $request): Response
    {
        $userId = (string) $request->user()?->getAuthIdentifier();
        $team = FindTeam::byIdForMember(id: $request->user()?->current_team_id, userId: $userId);

        if ($team->isNull()) {
            throw CouldNotViewTeam::notFound();
        }

        Gate::authorize('manageAccessRules', $team);
        $teamId = $team->getId()->toString();

        return Inertia::render('settings/access-rules', [
            'rules' => FindAccessRules::allForTeam($teamId)
                ->map(fn (AccessRule $rule): array => $rule->toListItem())
                ->values()
                ->all(),
            'options' => AccessRuleFormOptions::forTeam($teamId, $userId),
        ]);
    }
}
