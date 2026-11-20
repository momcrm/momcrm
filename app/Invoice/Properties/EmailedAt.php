<?php

declare(strict_types=1);

namespace App\Invoice\Properties;

use Mom\Data\AbstractDate;

/**
 * When the invoice was last emailed to the customer.
 */
final class EmailedAt extends AbstractDate
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'emailed_at';
    }
}
