<?php

declare(strict_types=1);

namespace App\MailboxMessage\Properties;

use Mom\Data\AbstractString;

/**
 * Why the message could not be sent.
 */
final class Error extends AbstractString
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'error';
    }
}
