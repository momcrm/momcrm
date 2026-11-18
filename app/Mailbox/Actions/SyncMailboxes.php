<?php

declare(strict_types=1);

namespace App\Mailbox\Actions;

use App\Mailbox\Jobs\SyncMailboxJob;
use App\Mailbox\Mailbox;

/**
 * Queues a read of every connected mailbox, one job each, so a slow or broken
 * mailbox holds up nobody else's mail.
 */
final class SyncMailboxes
{
    /**
     * The number of mailboxes queued.
     */
    public static function run(): int
    {
        return FindMailbox::connected()
            ->each(fn (Mailbox $mailbox) => SyncMailboxJob::dispatch($mailbox->getId()->toString()))
            ->count();
    }
}
