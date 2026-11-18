<?php

declare(strict_types=1);

namespace App\Mailbox\Jobs;

use App\Mailbox\Actions\FindMailbox;
use App\Mailbox\Actions\SyncMailbox;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Reads one mailbox's new mail. Only one read of a mailbox is queued or
 * running at a time, so the schedule and "Check now" never read it twice at
 * once. A failed read is not retried: the next scheduled read does that.
 */
final class SyncMailboxJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    /**
     * Shorter than the queue's retry_after, so a running read is never handed
     * to a second worker.
     */
    public int $timeout = 75;

    /**
     * How long the one-at-a-time lock is kept if a read dies without releasing it.
     */
    public int $uniqueFor = 600;

    public function __construct(
        public readonly string $mailboxId,
    ) {}

    public function uniqueId(): string
    {
        return $this->mailboxId;
    }

    public function handle(): void
    {
        SyncMailbox::fromMailbox(FindMailbox::byId($this->mailboxId));
    }
}
