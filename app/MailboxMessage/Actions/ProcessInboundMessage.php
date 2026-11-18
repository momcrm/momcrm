<?php

declare(strict_types=1);

namespace App\MailboxMessage\Actions;

use App\Contact\Actions\FindContact;
use App\Mailbox\Mailbox;
use App\MailboxMessage\MailboxMessage;
use App\MailboxMessage\MailboxMessageDirectionEnum;
use App\MailboxMessage\MailboxMessageStatusEnum;
use App\MailboxMessage\Properties\InternetMessageId;
use App\Ticket\Actions\CreateTicket;
use App\Ticket\Properties\Title;
use App\Ticket\Ticket;
use Illuminate\Support\Facades\DB;

/**
 * Takes in one email that arrived in a connected mailbox. The message is kept
 * once per team, however often it is read and whichever of the team's
 * mailboxes it reached, and opens a ticket when the mailbox opens tickets.
 */
final readonly class ProcessInboundMessage
{
    /**
     * Longest ticket description, in characters and in bytes: what people may
     * type, and what the column holds.
     */
    private const int DESCRIPTION_MAX_CHARACTERS = 50000;

    private const int DESCRIPTION_MAX_BYTES = 60000;

    public function __construct(
        private Mailbox $mailbox,
        private MailboxMessage $message,
    ) {}

    /**
     * The message as kept, or an empty one when the team already had it.
     */
    public function __invoke(): MailboxMessage
    {
        $teamId = $this->mailbox->getTeamId()->toString();
        $internetMessageId = $this->message->getInternetMessageId()->toNullableString()
            ?? InternetMessageId::normalize('microsoft:'.$this->message->getMicrosoftId()->toString());

        return DB::transaction(function () use ($teamId, $internetMessageId): MailboxMessage {
            if (FindMailboxMessage::byInternetMessageIdForTeam($internetMessageId, $teamId)->isNotNull()) {
                return MailboxMessage::new();
            }

            $ticket = $this->opensTicket() ? $this->openTicket($teamId) : Ticket::new();

            return CreateMailboxMessage::fromMessage(
                $this->message
                    ->setTeamId($teamId)
                    ->setMailboxId($this->mailbox->getId())
                    ->setInternetMessageId($internetMessageId)
                    ->setDirection(MailboxMessageDirectionEnum::Inbound)
                    ->setStatus($ticket->isNull() ? MailboxMessageStatusEnum::Ignored : MailboxMessageStatusEnum::Processed)
                    ->setTicketId($ticket->getId()),
            );
        });
    }

    public static function fromMessage(Mailbox $mailbox, MailboxMessage $message): MailboxMessage
    {
        return new self($mailbox, $message)->handle();
    }

    public function handle(): MailboxMessage
    {
        return $this->__invoke();
    }

    public function execute(): MailboxMessage
    {
        return $this->__invoke();
    }

    /**
     * Mail with no sender cannot be answered, and mail the mailbox sent
     * itself would open a ticket for every message it sends.
     */
    private function opensTicket(): bool
    {
        $from = $this->message->getFromAddress()->toNullableString();

        return $this->mailbox->getAutoCreateTickets()->toBoolean()
            && $from !== null
            && $from !== $this->mailbox->getAddress()->toString();
    }

    /**
     * A ticket from the message: its subject as the title, its text as the
     * description and its sender as the requester. A sender who is one of the
     * team's contacts also gives the ticket its contact and customer.
     */
    private function openTicket(string $teamId): Ticket
    {
        $from = $this->message->getFromAddress()->toString();
        $contact = FindContact::byEmailForTeam($from, $teamId);

        return CreateTicket::fromTicket(
            Ticket::new()
                ->setTeamId($teamId)
                ->setTitle(mb_substr($this->message->getSubject()->toNullableString() ?? '(no subject)', 0, Title::MAX_LENGTH))
                ->setDescription(self::description($this->message->getBodyText()->toNullableString()))
                ->setMailboxId($this->mailbox->getId())
                ->setGroupId($this->mailbox->getGroupId())
                ->setRequesterEmail($from)
                ->setRequesterName($this->message->getFromName())
                ->setContactId($contact->getId())
                ->setCustomerId($contact->getCustomerId()),
        );
    }

    private static function description(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        return mb_strcut(mb_substr($text, 0, self::DESCRIPTION_MAX_CHARACTERS), 0, self::DESCRIPTION_MAX_BYTES);
    }
}
