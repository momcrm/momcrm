<?php

declare(strict_types=1);

namespace App\Mailbox\Properties;

use Mom\Data\AbstractString;

/**
 * The mailbox's email address, in lower case.
 */
final class Address extends AbstractString
{
    public static function getName(): string
    {
        return 'address';
    }
}
