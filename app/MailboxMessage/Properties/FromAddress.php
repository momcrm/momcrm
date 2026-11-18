<?php

declare(strict_types=1);

namespace App\MailboxMessage\Properties;

use Mom\Data\AbstractString;

/**
 * The sender's email address, in lower case.
 */
final class FromAddress extends AbstractString
{
    public static function getName(): string
    {
        return 'from_address';
    }
}
