<?php

declare(strict_types=1);

namespace App\Invoice\Properties;

use Mom\Data\AbstractString;

/**
 * What the invoice's number starts with: its team's invoice prefix when it
 * was drafted.
 */
final class Prefix extends AbstractString
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'prefix';
    }

    public static function forEloquentFactoryValue(): string
    {
        return 'INV-';
    }
}
