<?php

declare(strict_types=1);

namespace App\SocialAccount\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class UserId extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'user_id';
    }
}
