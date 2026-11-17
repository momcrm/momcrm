<?php

declare(strict_types=1);

namespace App\CustomField\Properties;

use App\Shared\Abstracts\AbstractUuid;

/**
 * The custom object a record link points at, when it links to a custom
 * object's records; null for every other field. Fixed once the field exists.
 */
final class RelationObjectId extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'relation_object_id';
    }
}
