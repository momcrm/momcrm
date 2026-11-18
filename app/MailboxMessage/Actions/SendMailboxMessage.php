<?php

declare(strict_types=1);

namespace App\MailboxMessage\Actions;

use App\Connector\Actions\FindConnector;
use App\Connector\Exceptions\CouldNotReachConnector;
use App\Mailbox\Actions\FindMailbox;
use App\Mailbox\MicrosoftGraph;
use App\MailboxMessage\MailboxMessage;
use App\MailboxMessage\MailboxMessageStatusEnum;
use App\MailboxMessage\Properties\InternetMessageId;
use App\Shared\Types;
use App\Ticket\Actions\FindTicket;
use App\Ticket\Ticket;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Emails a waiting reply to the customer from its mailbox.
 *
 * When the customer has written in on the ticket, the email is a reply to
 * their last message, so it threads under it in their mail. Otherwise it
 * starts a thread. Either way its subject carries the ticket number, and the
 * id Microsoft gives it is kept, so the customer's answer finds the ticket.
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
                ? __('Microsoft could not be reached, so this reply was not sent.')
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
            throw CouldNotReachConnector::needsReconnect($connector->getType()->toEnum());
        }

        if ($ticket->isNull() || $to === []) {
            throw CouldNotReachConnector::refused((string) __('This ticket has no email address to reply to.'));
        }

        $graph = MicrosoftGraph::forMailbox($mailbox);
        $content = [
            'body' => ['contentType' => 'html', 'content' => nl2br(e($this->message->getBodyText()->toString()), false)],
            'toRecipients' => array_map(fn (string $address): array => ['emailAddress' => ['address' => $address]], $to),
        ];

        $answered = FindMailboxMessage::latestInboundOnTicket($ticket->getId()->toString(), $teamId, $mailbox->getId()->toString());
        $reply = $answered->isNull() ? null : $graph->createReplyDraft($answered->getExternalId()->toString());

        $draft = $reply === null
            ? $graph->createDraft(['subject' => self::subject($ticket, $ticket->getTitle()->toString()), ...$content])
            : $graph->updateDraft(Types::string($reply['id'] ?? null), [
                'subject' => self::subject($ticket, is_string($reply['subject'] ?? null) ? $reply['subject'] : $ticket->getTitle()->toString()),
                ...$content,
            ]);

        $graph->sendDraft(Types::string($draft['id'] ?? null));

        return UpdateMailboxMessage::fromMessage(
            $this->message
                ->setStatus(MailboxMessageStatusEnum::Sent)
                ->setError(null)
                ->setExternalId($draft['id'] ?? null)
                ->setInternetMessageId(InternetMessageId::normalize($draft['internetMessageId'] ?? null) ?? $this->message->getInternetMessageId())
                ->setSubject($draft['subject'] ?? null)
                ->setReceivedAt(CarbonImmutable::now()),
        );
    }

    /**
     * The subject with the ticket's number in it, such as "Printer offline
     * [#123]", unless it is already there.
     */
    private static function subject(Ticket $ticket, string $subject): string
    {
        $number = "[#{$ticket->getNumber()->toInteger()}]";

        return str_contains($subject, $number) ? $subject : mb_trim("{$subject} {$number}");
    }
}
