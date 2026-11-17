<?php

declare(strict_types=1);

namespace App\CustomField\Properties;

use App\Shared\Abstracts\AbstractUuid;

/**
 * The custom object the field belongs to; null for a field on a built-in
 * kind of record. Fixed once the field exists.
 */
final class ObjectId extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'object_id';
    }
}
