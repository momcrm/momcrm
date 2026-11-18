<?php

declare(strict_types=1);

namespace App\Connector\Actions;

use App\Connector\Connector;
use App\Connector\ConnectorEloquentModel;
use App\Mailbox\Actions\DeleteMailbox;
use App\Mailbox\Actions\FindMailbox;
use App\Mailbox\Mailbox;
use App\Team\Scopes\CurrentTeamScope;

/**
 * Disconnects an outside account: its credentials are forgotten, and the
 * mailboxes that went through it are removed with it. Tickets they opened stay.
 */
final readonly class DeleteConnector
{
    public function __construct(
        private Connector $connector,
    ) {}

    public function __invoke(): void
    {
        FindMailbox::forConnector($this->connector->getId()->toString())
            ->each(fn (Mailbox $mailbox) => DeleteMailbox::fromMailbox($mailbox));

        ConnectorEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->connector->getId()->toString())
            ?->delete();

        GetConnectorAccessToken::forget($this->connector);
    }

    public static function fromConnector(Connector $connector): void
    {
        new self($connector)->handle();
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
