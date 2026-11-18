<?php

declare(strict_types=1);

namespace App\MailboxMessage\Properties;

use Mom\Data\AbstractCollection;

/**
 * The addresses the message was sent to, in lower case.
 */
final class ToRecipients extends AbstractCollection
{
    public static function getName(): string
    {
        return 'to_recipients';
    }
}
