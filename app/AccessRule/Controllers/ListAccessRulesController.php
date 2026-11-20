<?php

declare(strict_types=1);

namespace App\AccessRule\Controllers;

use App\AccessRule\AccessAudienceEnum;
use App\AccessRule\AccessRule;
use App\AccessRule\AccessRuleFormOptions;
use App\AccessRule\Actions\FindAccessRules;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The team's access rules, for owners and admins to manage: those for
 * staff and those for customer contacts in the portal, one audience at a time.
 */
final class ListAccessRulesController
{
    /**
     * Back to the rules for the audience someone was working on.
     */
    public static function redirectTo(AccessAudienceEnum $audience): RedirectResponse
    {
        return to_route('access-rules.index', $audience === AccessAudienceEnum::Staff ? [] : ['audience' => $audience->value]);
    }

    /**
     * @throws CouldNotViewTeam
     */
    public function __invoke(Request $request): Response
    {
        $userId = (string) $request->user()?->id;
        $team = FindTeam::byIdForMember(id: $request->user()?->current_team_id, userId: $userId);

        if ($team->isNull()) {
            throw CouldNotViewTeam::notFound();
        }

        Gate::authorize('manageAccessRules', $team);
        $teamId = $team->getId()->toString();

        return Inertia::render('settings/access-rules', [
            'audience' => (AccessAudienceEnum::tryFrom($request->string('audience')->toString()) ?? AccessAudienceEnum::Staff)->value,
            'audiences' => AccessAudienceEnum::options(),
            'rules' => FindAccessRules::allForTeam($teamId)
                ->map(fn (AccessRule $rule): array => $rule->toListItem())
                ->values()
                ->all(),
            'options' => collect(AccessAudienceEnum::cases())
                ->mapWithKeys(fn (AccessAudienceEnum $audience): array => [$audience->value => AccessRuleFormOptions::forTeam($teamId, $userId, $audience)])
                ->all(),
        ]);
    }
}
