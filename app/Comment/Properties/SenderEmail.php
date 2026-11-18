<?php

declare(strict_types=1);

namespace App\Comment\Properties;

use Mom\Data\AbstractString;

/**
 * The address of the person whose email this comment is, in lower case;
 * empty for comments written in the app.
 */
final class SenderEmail extends AbstractString
{
    public static bool $includeInDatabaseUpdate = false;

    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'sender_email';
    }
}
