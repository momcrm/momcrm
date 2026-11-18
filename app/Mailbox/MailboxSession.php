<?php

declare(strict_types=1);

namespace App\Mailbox;

/**
 * Session keys used while someone is away at Microsoft connecting a mailbox.
 */
final class MailboxSession
{
    /**
     * The shared mailbox being connected; absent when it is the person's own.
     */
    public const string PENDING_SHARED_ADDRESS = 'mailbox.pending_shared_address';
}
