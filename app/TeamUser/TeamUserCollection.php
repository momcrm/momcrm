<?php

declare(strict_types=1);

namespace App\TeamUser;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, TeamUser>
 */
final class TeamUserCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return TeamUser::class;
    }
}
