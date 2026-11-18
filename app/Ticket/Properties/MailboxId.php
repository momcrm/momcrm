<?php

declare(strict_types=1);

namespace App\Ticket\Properties;

use App\Shared\Abstracts\AbstractUuid;

/**
 * The mailbox whose mail opened the ticket, and which answers it; empty for
 * tickets people opened themselves.
 */
final class MailboxId extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'mailbox_id';
    }
}
