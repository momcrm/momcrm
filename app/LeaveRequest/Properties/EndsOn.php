<?php

declare(strict_types=1);

namespace App\LeaveRequest\Properties;

use App\Shared\Abstracts\AbstractDateOnly;

final class EndsOn extends AbstractDateOnly
{
    public static function getName(): string
    {
        return 'ends_on';
    }
}
