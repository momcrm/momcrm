<?php

declare(strict_types=1);

namespace App\Ticket\Actions;

use App\Ticket\Notifications\TicketDueSoonNotification;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;

/**
 * Tells each assignee about their open tickets due tomorrow. Runs daily.
 */
final class RemindDueTickets
{
    /**
     * @return int how many reminders were sent
     */
    public static function run(?CarbonImmutable $today = null): int
    {
        $tickets = FindTicket::openDueOn(($today ?? CarbonImmutable::today())->addDay()->toDateString());

        $tickets->each(function (TicketEloquentModel $ticket): void {
            UserEloquentModel::query()->find($ticket->assignee_id)?->notify(new TicketDueSoonNotification($ticket));
        });

        return $tickets->count();
    }
}
