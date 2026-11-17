<?php

declare(strict_types=1);

namespace App\Record\Properties;

use App\Shared\Abstracts\AbstractUuid;

/**
 * Who added the record; null once that person is gone.
 */
final class CreatedById extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'created_by_id';
    }
}
