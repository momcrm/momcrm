<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Ticket\Contracts\ChangesTicket;
use App\Ticket\Events\TicketChanged;
use App\Ticket\Properties\CustomerId;
use Illuminate\Database\Eloquent\Model;

/**
 * Tells open pages, the customer portal's included, about tickets whose
 * models were created, updated or deleted.
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

        if ($model instanceof TicketEloquentModel && $model->wasChanged(CustomerId::getDatabaseTableColumnName())) {
            $this->announceToFormerCustomer($model, $model->getOriginal(CustomerId::getDatabaseTableColumnName()));
        }
    }

    public function deleted(Model&ChangesTicket $model): void
    {
        $this->announce($model);

        if ($model instanceof TicketEloquentModel) {
            $this->announceToFormerCustomer($model, $model->customer_id);
        }
    }

    private function announce(ChangesTicket $model): void
    {
        foreach ($model->changedTicketIds() as $ticketId) {
            TicketChanged::later($model->changedTicketTeamId(), $ticketId);
        }
    }

    /**
     * The customer a ticket left still lists it in their portal until told.
     */
    private function announceToFormerCustomer(TicketEloquentModel $ticket, mixed $customerId): void
    {
        if (is_string($customerId)) {
            TicketChanged::laterForFormerCustomer($ticket->team_id, $ticket->id, $customerId);
        }
    }
}
