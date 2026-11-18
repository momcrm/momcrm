<?php

declare(strict_types=1);

namespace App\Connector\Properties;

use App\Shared\Abstracts\AbstractUuid;

/**
 * Who connected the outside account.
 */
final class ConnectedById extends AbstractUuid
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'connected_by_id';
    }
}
