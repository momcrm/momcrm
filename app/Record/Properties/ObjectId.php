<?php

declare(strict_types=1);

namespace App\Record\Properties;

use App\Shared\Abstracts\AbstractUuid;

/**
 * The custom object the record is one of. A record never moves to another.
 */
final class ObjectId extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'object_id';
    }
}
