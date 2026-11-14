<?php

declare(strict_types=1);

namespace App\Team\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class OwnerId extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'owner_id';
    }
}
