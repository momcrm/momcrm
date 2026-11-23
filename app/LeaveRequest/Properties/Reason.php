<?php

declare(strict_types=1);

namespace App\LeaveRequest\Properties;

use Mom\Data\AbstractString;

final class Reason extends AbstractString
{
    public const int MAX = 1000;

    public static function getName(): string
    {
        return 'reason';
    }
}
