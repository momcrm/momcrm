<?php

declare(strict_types=1);

namespace App\MailboxMessage\Actions;

use App\Choice\Actions\FindChoice;
use App\Choice\ChoiceListEnum;
use App\Comment\Actions\CreateComment;
use App\Comment\Comment;
use App\Comment\CommentVisibilityEnum;
use App\Contact\Actions\FindContact;
use App\Mailbox\Mailbox;
use App\MailboxMessage\MailboxMessage;
use App\MailboxMessage\MailboxMessageDirectionEnum;
use App\MailboxMessage\MailboxMessageStatusEnum;
use App\MailboxMessage\Properties\InternetMessageId;
use App\Ticket\Actions\CreateTicket;
use App\Ticket\Actions\FindTicket;
use App\Ticket\Actions\UpdateTicket;
use App\Ticket\Properties\Title;
use App\Ticket\Ticket;
use App\Ticket\TicketStatusCategoryEnum;
use Illuminate\Support\Facades\DB;

/**
 * Takes in one email that arrived in a connected mailbox. The message is kept
 * once per team, however often it is read and whichever of the team's
 * mailboxes it reached.
 *
 * A message that answers one the team has on a ticket becomes a reply on that
 * ticket, and reopens it if it was closed. Anything else opens a new ticket,
 * when the mailbox opens tickets.
 */
final readonly class ProcessInboundMessage
{
    /**
     * Longest ticket description, in characters and in bytes: what people may
     * type, and what the column holds.
     */
    private const int DESCRIPTION_MAX_CHARACTERS = 50000;

    private const int TEXT_MAX_BYTES = 60000;

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

            $this->message
                ->setTeamId($teamId)
                ->setMailboxId($this->mailbox->getId())
                ->setInternetMessageId($internetMessageId)
                ->setDirection(MailboxMessageDirectionEnum::Inbound)
                ->setStatus(MailboxMessageStatusEnum::Ignored);

            $from = $this->message->getFromAddress()->toNullableString();

            // Mail with no sender cannot be answered, and mail the mailbox sent
            // itself would open a ticket for every message it sends.
            if ($from === null || $from === $this->mailbox->getAddress()->toString()) {
                return CreateMailboxMessage::fromMessage($this->message);
            }

            $ticket = $this->ticketAnswered($teamId, $from);

            if ($ticket->isNotNull()) {
                $this->message->setCommentId($this->addReply($ticket, $from)->getId());
            } elseif ($this->mailbox->getAutoCreateTickets()->toBoolean()) {
                $ticket = $this->openTicket($teamId, $from);
            }

            if ($ticket->isNotNull()) {
                $this->message->setStatus(MailboxMessageStatusEnum::Processed)->setTicketId($ticket->getId());
            }

            return CreateMailboxMessage::fromMessage($this->message);
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
     * The ticket this message is a reply on, if any.
     *
     * First by its headers: it answers, or follows, a message the team has on
     * a ticket. Failing that by a ticket number in its subject, such as
     * "[#123]", but only from someone already on that ticket, because anyone
     * can type a number into a subject. An archived ticket takes no replies,
     * so mail about it opens a new one.
     */
    private function ticketAnswered(string $teamId, string $from): Ticket
    {
        $ticket = FindTicket::byIdForTeam(
            FindMailboxMessage::nearestOnTicket($this->message->threadIds(), $teamId)->getTicketId()->toNullableString(),
            $teamId,
        );

        if ($ticket->isNull()) {
            $ticket = $this->ticketNamedInSubject($teamId, $from);
        }

        return $ticket->getArchivedAt()->isNull() ? $ticket : Ticket::new();
    }

    private function ticketNamedInSubject(string $teamId, string $from): Ticket
    {
        if (preg_match('/\[#(\d{1,9})\]/', $this->message->getSubject()->toString(), $matches) !== 1) {
            return Ticket::new();
        }

        $ticket = FindTicket::byNumberForTeam((int) $matches[1], $teamId);

        if ($ticket->isNull()) {
            return $ticket;
        }

        $contact = FindContact::byIdForTeam($ticket->getContactId()->toNullableString(), $teamId);
        $isOnTicket = $from === $ticket->getRequesterEmail()->toNullableString()
            || $from === mb_strtolower($contact->getEmail()->toString())
            || FindMailboxMessage::hasSenderOnTicket($ticket->getId()->toString(), $teamId, $from);

        return $isOnTicket ? $ticket : Ticket::new();
    }

    /**
     * Adds what the sender wrote, without the quoted thread beneath it, to
     * the ticket as part of the conversation with the customer, and reopens
     * the ticket if it was closed.
     */
    private function addReply(Ticket $ticket, string $from): Comment
    {
        $teamId = $ticket->getTeamId()->toString();
        $text = $this->message->getReplyText()->toNullableString() ?? $this->message->getBodyText()->toNullableString();

        $comment = CreateComment::fromComment(
            Comment::new()
                ->setTeamId($teamId)
                ->setTicketId($ticket->getId())
                ->setBody($text === null ? '(no text)' : mb_strcut($text, 0, self::TEXT_MAX_BYTES))
                ->setVisibility(CommentVisibilityEnum::Public)
                ->setSenderEmail($from)
                ->setSenderName($this->message->getFromName()),
        );

        $isClosed = FindChoice::byIdForTeam($ticket->getStatusId()->toNullableString(), $teamId)->hasCategory(TicketStatusCategoryEnum::Closed);
        $open = FindChoice::defaultForTeam($teamId, ChoiceListEnum::TicketStatus, TicketStatusCategoryEnum::Open->value);

        if ($isClosed && $open->isNotNull()) {
            UpdateTicket::fromTicket($ticket->setStatusId($open->getId()));
        }

        return $comment;
    }

    /**
     * A ticket from the message: its subject as the title, its text as the
     * description and its sender as the requester. A sender who is one of the
     * team's contacts also gives the ticket its contact and customer.
     */
    private function openTicket(string $teamId, string $from): Ticket
    {
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

        return mb_strcut(mb_substr($text, 0, self::DESCRIPTION_MAX_CHARACTERS), 0, self::TEXT_MAX_BYTES);
    }
}
