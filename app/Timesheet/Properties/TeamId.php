<?php

declare(strict_types=1);

namespace App\Timesheet\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class TeamId extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'team_id';
    }
}
