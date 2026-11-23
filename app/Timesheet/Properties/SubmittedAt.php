<?php

declare(strict_types=1);

namespace App\Timesheet\Properties;

use Mom\Data\AbstractDate;

final class SubmittedAt extends AbstractDate
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'submitted_at';
    }
}
