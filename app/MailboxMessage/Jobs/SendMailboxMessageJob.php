<?php

declare(strict_types=1);

namespace App\MailboxMessage\Jobs;

use App\MailboxMessage\Actions\FindMailboxMessage;
use App\MailboxMessage\Actions\SendMailboxMessage;
use App\MailboxMessage\MailboxMessageStatusEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Sends one waiting reply to the customer. It runs once and is never retried,
 * so the customer cannot get the same email twice.
 */
final class SendMailboxMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    /**
     * Shorter than the queue's retry_after, so a running send is never handed
     * to a second worker.
     */
    public int $timeout = 75;

    public function __construct(
        public readonly string $messageId,
    ) {}

    public function handle(): void
    {
        SendMailboxMessage::fromMessage(FindMailboxMessage::byId($this->messageId));
    }

    /**
     * A send that was cut short, for example by running out of time, must not
     * be left looking as if it is still on its way.
     */
    public function failed(?Throwable $exception): void
    {
        $message = FindMailboxMessage::byId($this->messageId);

        if ($message->isNotNull() && $message->getStatus()->toEnum() === MailboxMessageStatusEnum::Pending) {
            SendMailboxMessage::failed($message, __('Sending this reply was interrupted. Check the mailbox before sending it again.'));
        }
    }
}
