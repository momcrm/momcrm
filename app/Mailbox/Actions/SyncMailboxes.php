<?php

declare(strict_types=1);

namespace App\Mailbox\Actions;

use App\Mailbox\Jobs\SyncMailboxJob;
use App\Mailbox\Mailbox;
use App\Team\Actions\FindTeam;

/**
 * Queues a read of every connected mailbox, one job each, so a slow or broken
 * mailbox holds up nobody else's mail. A suspended team's mailboxes are left
 * unread: its mail waits on the server until the team is reactivated.
 */
final class SyncMailboxes
{
    /**
     * The number of mailboxes queued.
     */
    public static function run(): int
    {
        $suspendedTeamIds = FindTeam::suspendedIds();

        return FindMailbox::connected()
            ->reject(fn (Mailbox $mailbox): bool => in_array($mailbox->getTeamId()->toString(), $suspendedTeamIds, true))
            ->each(fn (Mailbox $mailbox) => SyncMailboxJob::dispatch($mailbox->getId()->toString()))
            ->count();
    }
}
