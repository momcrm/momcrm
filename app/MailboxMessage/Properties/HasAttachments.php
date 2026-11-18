<?php

declare(strict_types=1);

namespace App\MailboxMessage\Properties;

use Mom\Data\AbstractBoolean;

/**
 * Whether the mail service says files came with the message. It is known when
 * the message arrives and is not stored: the files themselves are saved.
 */
final class HasAttachments extends AbstractBoolean
{
    public static bool $hasDBColumn = false;

    public static bool $includeInArray = false;

    public static bool $includeInResponse = false;

    public static function getName(): string
    {
        return 'has_attachments';
    }

    public function default(): bool
    {
        return false;
    }
}
