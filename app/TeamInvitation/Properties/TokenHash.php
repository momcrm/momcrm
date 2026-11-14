<?php

declare(strict_types=1);

namespace App\TeamInvitation\Properties;

use Mom\Data\AbstractString;

final class TokenHash extends AbstractString
{
    public static bool $includeInDatabaseUpdate = false;

    public static bool $includeInArray = false;

    public static bool $includeInResponse = false;

    public static function getName(): string
    {
        return 'token_hash';
    }
}
