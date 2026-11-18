<?php

declare(strict_types=1);

namespace App\MailboxMessage\Properties;

use App\Shared\Abstracts\AbstractUuid;

/**
 * The ticket the message opened or was added to.
 */
final class TicketId extends AbstractUuid
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'ticket_id';
    }
}
