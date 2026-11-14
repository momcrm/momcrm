<?php

declare(strict_types=1);

namespace App\AuditLog\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class Id extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'id';
    }
}
