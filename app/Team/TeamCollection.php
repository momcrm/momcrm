<?php

declare(strict_types=1);

namespace App\Team;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Team>
 */
final class TeamCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Team::class;
    }
}
