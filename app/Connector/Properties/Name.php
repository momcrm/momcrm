<?php

declare(strict_types=1);

namespace App\Connector\Properties;

use Mom\Data\AbstractString;

/**
 * What people see the connector as: the name on the outside account, or its
 * address when it has none.
 */
final class Name extends AbstractString
{
    public static function getName(): string
    {
        return 'name';
    }
}
