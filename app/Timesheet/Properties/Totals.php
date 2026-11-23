<?php

declare(strict_types=1);

namespace App\Timesheet\Properties;

use Mom\Data\AbstractCollection;

/**
 * What an approved timesheet added up to, kept as it was approved: the
 * period's totals and each day's, as AttendancePeriod::forMember() gave them.
 */
final class Totals extends AbstractCollection
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'totals';
    }
}
