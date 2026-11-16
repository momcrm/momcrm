<?php

declare(strict_types=1);

namespace App\Customer\Properties;

use Mom\Data\AbstractInteger;

/**
 * The customer's services. Read from the query's count; not a column.
 */
final class ServicesCount extends AbstractInteger
{
    public static bool $hasDBColumn = false;

    public static function getName(): string
    {
        return 'services_count';
    }
}
