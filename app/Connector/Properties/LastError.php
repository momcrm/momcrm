<?php

declare(strict_types=1);

namespace App\Connector\Properties;

use Mom\Data\AbstractString;

/**
 * Why the last attempt to use the connector failed; empty when it worked.
 */
final class LastError extends AbstractString
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'last_error';
    }
}
