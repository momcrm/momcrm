<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Ticket\Contracts\ChangesTicket;
use App\Ticket\Events\TicketChanged;
use Illuminate\Database\Eloquent\Model;

/**
 * Tells open pages about tickets whose models were created, updated or
 * deleted.
 *
 * Only model events are seen: bulk query updates must announce their tickets
 * with TicketChanged::later() themselves.
 */
final class TicketChangeObserver
{
    public function created(Model&ChangesTicket $model): void
    {
        $this->announce($model);
    }

    public function updated(Model&ChangesTicket $model): void
    {
        $this->announce($model);
    }

    public function deleted(Model&ChangesTicket $model): void
    {
        $this->announce($model);
    }

    private function announce(ChangesTicket $model): void
    {
        foreach ($model->changedTicketIds() as $ticketId) {
            TicketChanged::later($model->changedTicketTeamId(), $ticketId);
        }
    }
}
