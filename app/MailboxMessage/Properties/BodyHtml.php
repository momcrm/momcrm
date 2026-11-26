<?php

declare(strict_types=1);

namespace App\MailboxMessage\Properties;

use Mom\Data\AbstractString;

/**
 * The message as its sender laid it out, for mail that came with HTML. It is
 * kept as it arrived, markup and all, and never leaves the server that way:
 * a page is only ever given it cleaned and boxed in (EmailHtml).
 */
final class BodyHtml extends AbstractString
{
    /**
     * The most HTML kept for one message, in bytes. A message laid out with
     * more than this is kept as its text alone.
     */
    public const int MAX_BYTES = 1_000_000;

    public static bool $includeInArray = false;

    public static bool $includeInResponse = false;

    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'body_html';
    }

    /**
     * The HTML worth keeping: none when there is nothing in it, when it is
     * too long, or when it is not text the database can hold.
     */
    public static function keepable(?string $html): ?string
    {
        if ($html === null || mb_trim($html) === '' || strlen($html) > self::MAX_BYTES || ! mb_check_encoding($html, 'UTF-8')) {
            return null;
        }

        return $html;
    }
}
