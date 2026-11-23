<?php

declare(strict_types=1);

namespace App\ShiftBreak\Properties;

use Mom\Data\AbstractDate;

final class StartedAt extends AbstractDate
{
    public static function getName(): string
    {
        return 'started_at';
    }
}
