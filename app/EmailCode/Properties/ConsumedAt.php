<?php

declare(strict_types=1);

namespace App\EmailCode\Properties;

use Mom\Data\AbstractDate;

final class ConsumedAt extends AbstractDate
{
    public static function getName(): string
    {
        return 'consumed_at';
    }
}
