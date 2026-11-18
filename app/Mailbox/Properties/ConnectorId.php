<?php

declare(strict_types=1);

namespace App\Mailbox\Properties;

use App\Shared\Abstracts\AbstractUuid;

/**
 * The connected account the mailbox is read and sent through.
 */
final class ConnectorId extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'connector_id';
    }
}
