<?php

declare(strict_types=1);

namespace App\Comment\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class TicketId extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'ticket_id';
    }
}
