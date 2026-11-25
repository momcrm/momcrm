<?php

declare(strict_types=1);

namespace App\Ticket;

use App\User\UserEloquentModel;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * The private channel that tells an open ticket it changed. Anyone who can see
 * the ticket in their current team may listen; tickets in a members-only
 * project stay hidden from people outside it.
 */
final class TicketChannel
{
    public const string NAME = 'tickets.{ticketId}';

    public static function for(string $ticketId): PrivateChannel
    {
        return new PrivateChannel(str_replace('{ticketId}', $ticketId, self::NAME));
    }

    public function join(UserEloquentModel $user, string $ticketId): bool
    {
        return TicketEloquentModel::query()->whereKey($ticketId)->exists();
    }
}
