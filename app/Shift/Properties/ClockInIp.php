<?php

declare(strict_types=1);

namespace App\Shift\Properties;

use Mom\Data\AbstractString;

final class ClockInIp extends AbstractString
{
    public static function getName(): string
    {
        return 'clock_in_ip';
    }
}
