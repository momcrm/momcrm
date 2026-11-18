<?php

declare(strict_types=1);

namespace App\Mailbox\Controllers;

use App\Connector\Actions\FindConnector;
use App\Connector\Connector;
use App\Connector\ConnectorCapabilityEnum;
use App\Group\Actions\FindGroup;
use App\Mailbox\Actions\FindMailbox;
use App\Mailbox\Contracts\ProvidesMail;
use App\Mailbox\Mailbox;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The team's mailboxes, and the connected accounts a mailbox can be added
 * through, for owners and admins to manage.
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

        $connectors = FindConnector::allForTeam($teamId);

        return Inertia::render('settings/email', [
            'mailboxes' => FindMailbox::allForTeam($teamId)
                ->map(fn (Mailbox $mailbox): array => $mailbox->toListItem($connectors->findById($mailbox->getConnectorId()->toString())))
                ->values()
                ->all(),
            'connectors' => $connectors
                ->filter(fn (Connector $connector): bool => $connector->isConnected() && $connector->hasCapability(ConnectorCapabilityEnum::Mail))
                ->map(fn (Connector $connector): array => [
                    'id' => $connector->getId()->toString(),
                    'name' => $connector->getName()->toString(),
                    'account' => $connector->getAccount()->toString(),
                    'type_label' => $connector->connectorType()->label(),
                    'shared_mailboxes' => self::opensSharedMailboxes($connector),
                ])
                ->values()
                ->all(),
            'groups' => FindGroup::optionsForTeam($teamId)
                ->map(fn (array $group): array => ['id' => $group['id'], 'name' => $group['name']])
                ->values()
                ->all(),
        ]);
    }

    private static function opensSharedMailboxes(Connector $connector): bool
    {
        $type = $connector->connectorType();

        return $type instanceof ProvidesMail && $type->opensSharedMailboxes($connector);
    }
}
