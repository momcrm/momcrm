<?php

declare(strict_types=1);

namespace App\MailboxMessage\Actions;

use App\Comment\Comment;
use App\MailboxMessage\Jobs\SendMailboxMessageJob;
use App\MailboxMessage\MailboxMessage;
use App\MailboxMessage\MailboxMessageDirectionEnum;
use App\MailboxMessage\MailboxMessageStatusEnum;
use App\MailboxMessage\Properties\Id;
use App\MailboxMessage\TicketReplyRoute;

/**
 * Lines up a reply to be emailed to the customer. The message is kept as
 * waiting straight away, so the ticket shows it is on its way, and is sent by
 * a queued job once the comment is saved.
 */
final readonly class QueueCommentEmail
{
    public function __construct(
        private Comment $comment,
        private TicketReplyRoute $route,
    ) {}

    public function __invoke(): MailboxMessage
    {
        $id = Id::generate();

        $message = CreateMailboxMessage::fromMessage(
            MailboxMessage::new()
                ->setId($id)
                ->setTeamId($this->comment->getTeamId())
                ->setMailboxId($this->route->mailbox->getId())
                ->setDirection(MailboxMessageDirectionEnum::Outbound)
                ->setStatus(MailboxMessageStatusEnum::Pending)
                // Every message needs an id of its own; Microsoft gives the real one when it is drafted.
                ->setInternetMessageId("pending:{$id->toString()}")
                ->setFromAddress($this->route->mailbox->getAddress())
                ->setToRecipients($this->route->to === null ? null : [$this->route->to])
                ->setBodyText($this->comment->getBody())
                ->setTicketId($this->comment->getTicketId())
                ->setCommentId($this->comment->getId()),
        );

        SendMailboxMessageJob::dispatch($message->getId()->toString())->afterCommit();

        return $message;
    }

    public static function forComment(Comment $comment, TicketReplyRoute $route): MailboxMessage
    {
        return new self($comment, $route)->handle();
    }

    public function handle(): MailboxMessage
    {
        return $this->__invoke();
    }

    public function execute(): MailboxMessage
    {
        return $this->__invoke();
    }
}
