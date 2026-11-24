<?php

declare(strict_types=1);

namespace App\Team\Properties;

use Mom\Data\AbstractDate;

/**
 * When the team was suspended. Nothing in a suspended team can be changed
 * until it is reactivated; its members can still sign in and read.
 */
final class SuspendedAt extends AbstractDate
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'suspended_at';
    }
}
