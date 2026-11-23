<?php

declare(strict_types=1);

namespace App\Shift\Properties;

use Mom\Data\AbstractDate;

final class ClockedOutAt extends AbstractDate
{
    public static function getName(): string
    {
        return 'clocked_out_at';
    }
}
