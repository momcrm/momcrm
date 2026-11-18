<?php

declare(strict_types=1);

namespace App\MailboxMessage\Properties;

use Mom\Data\AbstractString;

/**
 * The message as plain text.
 */
final class BodyText extends AbstractString
{
    public static function getName(): string
    {
        return 'body_text';
    }
}
