<?php

declare(strict_types=1);

namespace App\Ticket\Notifications;

use App\Inbox\InboxKindEnum;
use App\Inbox\InboxNotification;
use App\Inbox\InboxPayload;
use App\Ticket\TicketEloquentModel;

/**
 * Tells staff a customer's contact raised a ticket in the customer portal.
 */
final class TicketRaisedInPortalNotification extends InboxNotification
{
    public function __construct(
        public readonly TicketEloquentModel $ticket,
        public readonly string $contactName,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return InboxPayload::forTicket(InboxKindEnum::Update, $this->ticket, null, __('raised it in the portal'), actorName: $this->contactName);
    }
}
