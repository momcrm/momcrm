<?php

declare(strict_types=1);

namespace App\Mailbox\Properties;

use Mom\Data\AbstractString;

/**
 * Why the last attempt to reach the mailbox failed; empty when it worked.
 */
final class LastError extends AbstractString
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'last_error';
    }
}
