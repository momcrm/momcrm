<?php

declare(strict_types=1);

namespace App\Mailbox\Properties;

use App\Shared\Abstracts\AbstractUuid;

/**
 * Who signed in to Microsoft to connect the mailbox.
 */
final class ConnectedById extends AbstractUuid
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'connected_by_id';
    }
}
