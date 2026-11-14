<?php

declare(strict_types=1);

namespace App\Team\Properties;

use Mom\Data\AbstractBoolean;

final class IsPersonal extends AbstractBoolean
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'is_personal';
    }

    public static function getDatabaseTableColumnName(): string
    {
        return 'personal_team';
    }

    public function default(): bool
    {
        return false;
    }
}
