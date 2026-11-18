<?php

declare(strict_types=1);

namespace App\MailboxMessage\Properties;

use Mom\Data\AbstractString;

final class FromName extends AbstractString
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'from_name';
    }
}
