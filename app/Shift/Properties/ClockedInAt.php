<?php

declare(strict_types=1);

namespace App\Shift\Properties;

use Mom\Data\AbstractDate;

final class ClockedInAt extends AbstractDate
{
    public static function getName(): string
    {
        return 'clocked_in_at';
    }
}
