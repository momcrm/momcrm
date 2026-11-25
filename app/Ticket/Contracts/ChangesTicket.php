<?php

declare(strict_types=1);

namespace App\Ticket\Contracts;

/**
 * A model shown on a ticket, such as a comment or an emailed reply, whose
 * changes reach the ticket's open pages through TicketChangeObserver.
 */
interface ChangesTicket
{
    public function changedTicketTeamId(): string;

    /**
     * The tickets that show this model: none when it isn't on a ticket.
     *
     * @return list<string>
     */
    public function changedTicketIds(): array;
}
