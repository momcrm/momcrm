<?php

declare(strict_types=1);

namespace App\Connector\Properties;

use Mom\Data\AbstractString;

/**
 * Which outside account this is, in lower case: its address. Signing in to
 * the same account again reconnects this connector and does not add another.
 */
final class Account extends AbstractString
{
    public static function getName(): string
    {
        return 'account';
    }
}
