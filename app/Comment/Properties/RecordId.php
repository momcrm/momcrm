<?php

declare(strict_types=1);

namespace App\Comment\Properties;

use App\Shared\Abstracts\AbstractUuid;

/**
 * The custom object's record the comment is on; null for a comment on a
 * ticket. A comment is on one or the other.
 */
final class RecordId extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'record_id';
    }
}
