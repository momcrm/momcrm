<?php

declare(strict_types=1);

namespace App\MailboxMessage\Properties;

use Mom\Data\AbstractString;

/**
 * The message's Message-ID header, without its angle brackets. Every email
 * has one, replies name the ids of the messages they answer, and a team keeps
 * each message once by it.
 */
final class InternetMessageId extends AbstractString
{
    /**
     * As long as the column, and as much of an id as is compared.
     */
    public const int MAX_LENGTH = 500;

    public static function getName(): string
    {
        return 'internet_message_id';
    }

    /**
     * An id as it is stored and compared: no angle brackets or surrounding
     * space, and no longer than the column. Null when nothing is left.
     */
    public static function normalize(mixed $id): ?string
    {
        if (! is_string($id)) {
            return null;
        }

        $id = mb_substr(mb_trim($id, " \t\n\r\0\x0B<>"), 0, self::MAX_LENGTH);

        return $id === '' ? null : $id;
    }
}
