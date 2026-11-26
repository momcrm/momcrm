<?php

declare(strict_types=1);

namespace App\Mailbox\Actions;

use App\Connector\Actions\FindConnector;
use App\Mailbox\Contracts\SetsUpMailboxes;
use App\Mailbox\Mailbox;
use App\Mailbox\MailboxEloquentModel;
use App\Team\Scopes\CurrentTeamScope;
use Mom\Connectors\CouldNotReachConnector;

/**
 * Removes a mailbox: it is no longer read or sent from. Its connector and
 * the tickets it already opened stay.
 *
 * A mail service that was told to hand the mailbox's mail over is told to
 * stop. Removing the mailbox does not wait on that: when the service cannot
 * be reached the mailbox goes anyway, and mail the service still hands over
 * for it is turned away.
 */
final readonly class DeleteMailbox
{
    public function __construct(
        private Mailbox $mailbox,
    ) {}

    public function __invoke(): void
    {
        $connector = FindConnector::byId($this->mailbox->getConnectorId()->toNullableString());
        $type = $connector->connectorType();

        if ($type instanceof SetsUpMailboxes) {
            try {
                $type->tearDownMailbox($connector, $this->mailbox->getAddress()->toString());
            } catch (CouldNotReachConnector) {
                // The mailbox is removed all the same.
            }
        }

        MailboxEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->mailbox->getId()->toString())
            ?->delete();
    }

    public static function fromMailbox(Mailbox $mailbox): void
    {
        new self($mailbox)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }
}
