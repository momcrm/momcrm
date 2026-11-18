<?php

declare(strict_types=1);

namespace App\MailboxMessage\Properties;

use Mom\Data\AbstractString;

/**
 * What the sender wrote in this message, without the earlier messages quoted
 * beneath it. It is read from Microsoft when the message arrives and is not
 * stored: the whole message is in the body text.
 */
final class ReplyText extends AbstractString
{
    public static bool $hasDBColumn = false;

    public static bool $includeInArray = false;

    public static bool $includeInResponse = false;

    public static function getName(): string
    {
        return 'reply_text';
    }
}
