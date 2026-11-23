<?php

declare(strict_types=1);

namespace App\ShiftBreak\Properties;

use Mom\Data\AbstractDate;

final class EndedAt extends AbstractDate
{
    public static function getName(): string
    {
        return 'ended_at';
    }
}
