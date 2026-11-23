<?php

declare(strict_types=1);

namespace App\Shift\Properties;

use App\Shared\Abstracts\AbstractDateOnly;

final class WorkDate extends AbstractDateOnly
{
    public static function getName(): string
    {
        return 'work_date';
    }
}
