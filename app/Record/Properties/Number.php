<?php

declare(strict_types=1);

namespace App\Record\Properties;

use Mom\Data\AbstractInteger;

/**
 * The record's number within its object, counted from 1.
 */
final class Number extends AbstractInteger
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'number';
    }
}
