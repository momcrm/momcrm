<?php

declare(strict_types=1);

namespace App\Comment\Properties;

use Mom\Data\AbstractString;

/**
 * The name of the person whose email this comment is; empty for comments
 * written in the app.
 */
final class SenderName extends AbstractString
{
    public static bool $includeInDatabaseUpdate = false;

    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'sender_name';
    }
}
