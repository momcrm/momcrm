<?php

declare(strict_types=1);

namespace App\MailboxMessage\Actions;

use App\Connector\Actions\FindConnector;
use App\Mailbox\Actions\FindMailbox;
use App\Mailbox\Actions\ResolveMailClient;
use App\MailboxMessage\MailboxMessage;
use App\MailboxMessage\MailboxMessageStatusEnum;
use App\Shared\Types;
use App\Ticket\Actions\FindTicket;
use Carbon\CarbonImmutable;
use Mom\Connectors\CouldNotReachConnector;
use Mom\Connectors\Mail\OutgoingMail;
use Throwable;

/**
 * Emails a waiting reply to the customer from its mailbox.
 *
 * When the customer has written in on the ticket, the email is a reply to
 * their last message, so it threads under it in their mail. Otherwise it
 * starts a thread. Either way its subject carries the ticket number, and the
 * id the mail service gives it is kept, so the customer's answer finds the ticket.
 *
 * It is tried once. Whatever goes wrong is kept on the message, where the
 * ticket shows it, and the reply is not sent again by itself: a second attempt
 * could send the customer the same email twice.
 */
final readonly class SendMailboxMessage
{
    public function __construct(
        private MailboxMessage $message,
    ) {}

    public function __invoke(): MailboxMessage
    {
        if ($this->message->isNull() || $this->message->getStatus()->toEnum() !== MailboxMessageStatusEnum::Pending) {
            return $this->message;
        }

        try {
            return $this->send();
        } catch (CouldNotReachConnector $exception) {
            // Reading mail is tried again by itself; sending is not, so do not say it will be.
            return self::failed($this->message, $exception->isTemporary
                ? __(':company could not be reached, so this reply was not sent.', ['company' => $exception->company ?? __('The mail service')])
                : $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return self::failed($this->message, __('Something went wrong sending this reply.'));
        }
    }

    public static function fromMessage(MailboxMessage $message): MailboxMessage
    {
        return new self($message)->handle();
    }

    /**
     * Marks a reply as not sent, with the reason people see on the ticket.
     */
    public static function failed(MailboxMessage $message, string $reason): MailboxMessage
    {
        return UpdateMailboxMessage::fromMessage(
            $message->setStatus(MailboxMessageStatusEnum::Failed)->setError($reason),
        );
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
     * @throws CouldNotReachConnector
     */
    private function send(): MailboxMessage
    {
        $teamId = $this->message->getTeamId()->toString();
        $mailbox = FindMailbox::byId($this->message->getMailboxId()->toNullableString());
        $ticket = FindTicket::byIdForTeam($this->message->getTicketId()->toNullableString(), $teamId);
        $to = Types::stringList($this->message->getToRecipients()->toArray());

        $connector = FindConnector::byId($mailbox->getConnectorId()->toNullableString());

        if ($mailbox->isNull() || ! $connector->isConnected()) {
            throw CouldNotReachConnector::needsReconnect($connector->connectorType()->company());
        }

        if ($ticket->isNull() || $to === []) {
            throw CouldNotReachConnector::refused((string) __('This ticket has no email address to reply to.'));
        }

        $answered = FindMailboxMessage::latestInboundOnTicket($ticket->getId()->toString(), $teamId, $mailbox->getId()->toString());

        $sent = ResolveMailClient::forMailbox($mailbox)->send(new OutgoingMail(
            to: $to,
            bodyText: $this->message->getBodyText()->toString(),
            subject: $ticket->getTitle()->toString(),
            subjectTag: "[#{$ticket->getNumber()->toInteger()}]",
            answered: $answered->isNull() ? null : $answered->toInboundMail(),
        ));

        return UpdateMailboxMessage::fromMessage(
            $this->message
                ->setStatus(MailboxMessageStatusEnum::Sent)
                ->setError(null)
                ->setExternalId($sent->externalId)
                ->setInternetMessageId($sent->internetMessageId ?? $this->message->getInternetMessageId())
                ->setSubject($sent->subject)
                ->setReceivedAt(CarbonImmutable::now()),
        );
    }
}
