<?php

declare(strict_types=1);

namespace App\MailboxMessage;

use App\Contact\Actions\FindContact;
use App\Mailbox\Actions\FindMailbox;
use App\Mailbox\Mailbox;
use App\Ticket\Ticket;

/**
 * How a reply on a ticket reaches the customer: who it goes to, and which of
 * the team's mailboxes sends it.
 *
 * It goes to whoever wrote in, or else to the ticket's contact. It is sent
 * from the mailbox the ticket came in through, or else from the first mailbox
 * the team has connected.
 */
final readonly class TicketReplyRoute
{
    private function __construct(
        public ?string $to,
        public Mailbox $mailbox,
    ) {}

    public static function forTicket(Ticket $ticket): self
    {
        $teamId = $ticket->getTeamId()->toString();
        $to = $ticket->getRequesterEmail()->toNullableString()
            ?? FindContact::byIdForTeam($ticket->getContactId()->toNullableString(), $teamId)->getEmail()->toNullableString();
        $to = $to === null ? '' : mb_strtolower(mb_trim($to));

        return new self(
            $to === '' ? null : $to,
            FindMailbox::toSendFromForTeam($ticket->getMailboxId()->toNullableString(), $teamId),
        );
    }

    /**
     * Whether a reply can be sent at all.
     */
    public function isOpen(): bool
    {
        return $this->to !== null && $this->mailbox->isNotNull();
    }

    /**
     * For the comment box, which says who a reply goes to and where from.
     *
     * @return array{to: ?string, from: ?string}
     */
    public function toArray(): array
    {
        return [
            'to' => $this->to,
            'from' => $this->mailbox->getAddress()->toNullableString(),
        ];
    }
}
