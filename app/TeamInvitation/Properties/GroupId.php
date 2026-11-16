<?php

declare(strict_types=1);

namespace App\TeamInvitation\Properties;

use App\Shared\Abstracts\AbstractUuid;

/**
 * The group the invited person joins when they accept, if any.
 */
final class GroupId extends AbstractUuid
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'group_id';
    }
}
