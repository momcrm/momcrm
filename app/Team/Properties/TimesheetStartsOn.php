<?php

declare(strict_types=1);

namespace App\Team\Properties;

use App\Shared\Abstracts\AbstractDateOnly;

/**
 * A Monday that starts one of the team's fortnights, so every fortnight
 * after it lines up. Unused for weekly periods.
 */
final class TimesheetStartsOn extends AbstractDateOnly
{
    public static bool $includeInDatabaseCreate = false;

    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'timesheet_starts_on';
    }
}
