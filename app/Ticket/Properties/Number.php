<?php

declare(strict_types=1);

namespace App\Ticket\Properties;

use Mom\Data\AbstractInteger;

final class Number extends AbstractInteger
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'number';
    }
}
