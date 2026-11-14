<?php

declare(strict_types=1);

namespace App\Invoice\Properties;

use Mom\Data\AbstractDate;

final class PaidAt extends AbstractDate
{
    public static function getName(): string
    {
        return 'paid_at';
    }
}
