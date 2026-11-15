<?php

declare(strict_types=1);

namespace App\AccessRule\Properties;

use App\Shared\Abstracts\AbstractUuid;

/**
 * Who wrote the rule; empty for the rules every team starts with.
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
