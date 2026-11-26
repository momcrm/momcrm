<?php

declare(strict_types=1);

namespace App\Mailbox\Actions;

use App\Mailbox\Mailbox;
use App\MailboxMessage\Actions\ProcessInboundMessage;
use App\MailboxMessage\Actions\SaveMessageAttachments;
use App\MailboxMessage\MailboxMessage;
use Mom\Connectors\Mail\InboundMail;
use Mom\Connectors\Mail\MailClient;
use Throwable;

/**
 * Takes in one email from a mailbox: it is kept, and becomes a ticket or a
 * reply on one. Its attachments are saved on its ticket afterwards.
 *
 * A message we cannot make sense of is our fault, not the mail service's,
 * so it is reported and left out instead of stopping the mail behind it.
 * Attachments are the one thing not tried again: if they cannot be fetched
 * the ticket still stands, and the failure is reported.
 */
final readonly class TakeInMail
{
    public function __construct(
        private Mailbox $mailbox,
        private MailClient $client,
        private InboundMail $mail,
    ) {}

    /**
     * Whether the email was new and has been kept.
     */
    public function __invoke(): bool
    {
        try {
            $kept = ProcessInboundMessage::fromMessage($this->mailbox, MailboxMessage::fromInboundMail($this->mail));
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }

        if ($kept->isNotNull() && $kept->getTicketId()->isNotNull() && $this->mail->hasAttachments) {
            try {
                SaveMessageAttachments::fromMessage($this->client, $kept);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $kept->isNotNull();
    }

    public static function fromMail(Mailbox $mailbox, MailClient $client, InboundMail $mail): bool
    {
        return new self($mailbox, $client, $mail)->handle();
    }

    public function handle(): bool
    {
        return $this->__invoke();
    }

    public function execute(): bool
    {
        return $this->__invoke();
    }
}
