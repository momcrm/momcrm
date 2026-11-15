<?php

declare(strict_types=1);

namespace App\Ticket\Actions;

use App\Team\Scopes\CurrentTeamScope;
use App\Ticket\Notifications\TicketDueSoonNotification;
use App\Ticket\Properties\ArchivedAt;
use App\Ticket\Properties\AssigneeId;
use App\Ticket\Properties\DueOn;
use App\Ticket\Properties\StatusId;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketRelationship;
use App\Ticket\TicketStatusCategoryEnum;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;

/**
 * Tells each assignee about their open tickets due tomorrow, in every team.
 * Runs daily. The notifications are queued, so they take the models.
 */
final class RemindDueTickets
{
    /**
     * @return int how many reminders were sent
     */
    public static function run(?CarbonImmutable $today = null): int
    {
        $tickets = TicketEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->with(TicketRelationship::Project->value)
            ->whereNull(ArchivedAt::getDatabaseTableColumnName())
            ->whereNotNull(AssigneeId::getDatabaseTableColumnName())
            ->whereIn(StatusId::getDatabaseTableColumnName(), TicketStatusCategoryEnum::Open->statusIds())
            ->whereDate(DueOn::getDatabaseTableColumnName(), ($today ?? CarbonImmutable::today())->addDay()->toDateString())
            ->get();

        $tickets->each(function (TicketEloquentModel $ticket): void {
            UserEloquentModel::query()->find($ticket->assignee_id)?->notify(new TicketDueSoonNotification($ticket));
        });

        return $tickets->count();
    }
}
