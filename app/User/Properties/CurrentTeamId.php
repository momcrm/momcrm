<?php

declare(strict_types=1);

namespace App\User\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class CurrentTeamId extends AbstractUuid
{
    public static function getName(): string
    {
        return 'current_team_id';
    }
}
