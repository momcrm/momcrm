<?php

declare(strict_types=1);

namespace App\TeamInvitation\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class InvitedById extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'invited_by_id';
    }
}
