<?php

declare(strict_types=1);

namespace App\TeamUser\Properties;

use App\Shared\Abstracts\AbstractUuid;

/**
 * The schedule the member works to, or null to follow the team's default.
 */
final class WorkScheduleId extends AbstractUuid
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'work_schedule_id';
    }
}
