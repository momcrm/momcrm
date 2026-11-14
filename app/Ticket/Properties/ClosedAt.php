<?php

declare(strict_types=1);

namespace App\Ticket\Properties;

use Mom\Data\AbstractDate;

final class ClosedAt extends AbstractDate
{
    public static function getName(): string
    {
        return 'closed_at';
    }
}
