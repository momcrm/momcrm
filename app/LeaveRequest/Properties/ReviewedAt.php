<?php

declare(strict_types=1);

namespace App\LeaveRequest\Properties;

use Mom\Data\AbstractDate;

final class ReviewedAt extends AbstractDate
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'reviewed_at';
    }
}
