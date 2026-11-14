<?php

declare(strict_types=1);

namespace App\SocialAccount\Properties;

use Mom\Data\AbstractString;

final class ProviderUserId extends AbstractString
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'provider_user_id';
    }
}
