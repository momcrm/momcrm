<?php

declare(strict_types=1);

namespace App\Mailbox\Actions;

use App\Mailbox\Mailbox;
use App\Mailbox\MailboxEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

/**
 * Disconnects a mailbox: its sign-in is forgotten and it is no longer read.
 * Tickets it already opened stay.
 */
final readonly class DeleteMailbox
{
    public function __construct(
        private Mailbox $mailbox,
    ) {}

    public function __invoke(): void
    {
        MailboxEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->mailbox->getId()->toString())
            ?->delete();

        GetMailboxAccessToken::forget($this->mailbox);
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
