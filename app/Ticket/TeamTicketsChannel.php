<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Team\Actions\FindTeam;
use App\User\UserEloquentModel;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * The private channel that tells a team's ticket lists one of its tickets
 * changed. Only the ticket's id is sent, so every member may listen: each
 * list reloads what its reader is allowed to see.
 */
final class TeamTicketsChannel
{
    public const string NAME = 'teams.{teamId}.tickets';

    public static function for(string $teamId): PrivateChannel
    {
        return new PrivateChannel(str_replace('{teamId}', $teamId, self::NAME));
    }

    public function join(UserEloquentModel $user, string $teamId): bool
    {
        return FindTeam::byIdForMember($teamId, $user->id)->isNotNull();
    }
}
