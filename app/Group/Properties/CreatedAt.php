<?php

declare(strict_types=1);

namespace App\Group\Properties;

use Mom\Data\AbstractDate;

final class CreatedAt extends AbstractDate
{
    public static bool $includeInDatabaseCreate = false;

    public static bool $includeInDatabaseUpdate = false;

    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'created_at';
    }
}
