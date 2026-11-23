<?php

declare(strict_types=1);

namespace App\Holiday\Properties;

use App\Shared\Abstracts\AbstractDateOnly;

final class Date extends AbstractDateOnly
{
    public static function getName(): string
    {
        return 'date';
    }
}
