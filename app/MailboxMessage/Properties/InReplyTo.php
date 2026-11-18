<?php

declare(strict_types=1);

namespace App\MailboxMessage\Properties;

use Mom\Data\AbstractString;

/**
 * The id of the message this one answers, from the In-Reply-To header.
 */
final class InReplyTo extends AbstractString
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'in_reply_to';
    }
}
