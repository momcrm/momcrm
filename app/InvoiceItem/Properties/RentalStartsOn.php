<?php

declare(strict_types=1);

namespace App\InvoiceItem\Properties;

use App\Shared\Abstracts\AbstractDateOnly;

/**
 * The first day a line bills its service for.
 */
final class RentalStartsOn extends AbstractDateOnly
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'rental_starts_on';
    }
}
