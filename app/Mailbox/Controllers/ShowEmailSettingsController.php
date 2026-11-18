<?php

declare(strict_types=1);

namespace App\Mailbox\Controllers;

use App\Group\Actions\FindGroup;
use App\Mailbox\Actions\FindMailbox;
use App\Mailbox\Mailbox;
use App\SocialAccount\SocialProviderEnum;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The mailboxes the team has connected, for owners and admins to manage.
 */
final class ShowEmailSettingsController
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

        Gate::authorize('manageEmail', $team);
        $teamId = $team->getId()->toString();

        return Inertia::render('settings/email', [
            'mailboxes' => FindMailbox::allForTeam($teamId)
                ->map(fn (Mailbox $mailbox): array => $mailbox->toListItem())
                ->values()
                ->all(),
            'groups' => FindGroup::optionsForTeam($teamId)
                ->map(fn (array $group): array => ['id' => $group['id'], 'name' => $group['name']])
                ->values()
                ->all(),
            'canConnect' => SocialProviderEnum::Microsoft->isConfigured(),
        ]);
    }
}
