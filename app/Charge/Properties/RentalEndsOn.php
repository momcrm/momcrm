<?php

declare(strict_types=1);

namespace App\Charge\Properties;

use App\Shared\Abstracts\AbstractDateOnly;

/**
 * The last day of the service period the charge bills, when it bills one.
 */
final class RentalEndsOn extends AbstractDateOnly
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'rental_ends_on';
    }
}
