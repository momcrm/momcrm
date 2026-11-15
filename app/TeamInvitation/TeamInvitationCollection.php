<?php

declare(strict_types=1);

namespace App\TeamInvitation;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, TeamInvitation>
 */
final class TeamInvitationCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return TeamInvitation::class;
    }
}
