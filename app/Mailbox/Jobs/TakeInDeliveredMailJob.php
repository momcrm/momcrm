<?php

declare(strict_types=1);

namespace App\Mailbox\Jobs;

use App\Connector\Actions\FindConnector;
use App\Mailbox\Actions\FindMailbox;
use App\Mailbox\Actions\ResolveMailClient;
use App\Mailbox\Actions\TakeInMail;
use App\MailboxMessage\Actions\FindMailboxMessage;
use App\Team\Actions\FindTeam;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Takes in one email a mail service has just handed over for a mailbox, so
 * it becomes a ticket straight away instead of at the next read.
 *
 * Nothing is lost when this does not run or fails: the email stays in the
 * mailbox, and the next scheduled read takes it in. That is also why a
 * suspended team's mail is left where it is.
 */
final class TakeInDeliveredMailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    /**
     * Shorter than the queue's retry_after, so a running job is never handed
     * to a second worker.
     */
    public int $timeout = 75;

    public function __construct(
        public readonly string $mailboxId,
        public readonly string $externalId,
    ) {}

    public function handle(): void
    {
        $mailbox = FindMailbox::byId($this->mailboxId);

        if ($mailbox->isNull()
            || in_array($mailbox->getTeamId()->toString(), FindTeam::suspendedIds(), true)
            || ! FindConnector::byId($mailbox->getConnectorId()->toNullableString())->isConnected()
            || FindMailboxMessage::knownExternalIds($this->mailboxId, $mailbox->getTeamId()->toString(), [$this->externalId]) !== []) {
            return;
        }

        $client = ResolveMailClient::forMailbox($mailbox);
        $mail = $client->message($this->externalId);

        if ($mail !== null) {
            TakeInMail::fromMail($mailbox, $client, $mail);
        }
    }
}
