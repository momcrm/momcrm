<?php

declare(strict_types=1);

namespace App\Timesheet\Properties;

use App\Shared\Abstracts\AbstractDateOnly;

final class StartsOn extends AbstractDateOnly
{
    public static function getName(): string
    {
        return 'starts_on';
    }
}
