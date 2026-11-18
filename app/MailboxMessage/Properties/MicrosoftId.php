<?php

declare(strict_types=1);

namespace App\MailboxMessage\Properties;

use Mom\Data\AbstractString;

/**
 * Microsoft's own id for the message, which stays the same when the message
 * is moved to another folder.
 */
final class MicrosoftId extends AbstractString
{
    public static function getName(): string
    {
        return 'microsoft_id';
    }
}
