<?php

declare(strict_types=1);

namespace App\InvoiceItem\Properties;

use App\Shared\Abstracts\AbstractDateOnly;

/**
 * The last day a line bills its service for.
 */
final class RentalEndsOn extends AbstractDateOnly
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'rental_ends_on';
    }
}
