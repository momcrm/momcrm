<?php

declare(strict_types=1);

namespace App\Ticket\Notifications;

use App\Inbox\InboxKindEnum;
use App\Inbox\InboxNotification;
use App\Inbox\InboxPayload;
use App\Ticket\TicketEloquentModel;
use Illuminate\Support\Str;

/**
 * Tells staff a customer raised a ticket, by email or in the customer
 * portal. The customer is named as there is no actor in the team.
 */
final class TicketRaisedNotification extends InboxNotification
{
    public function __construct(
        public readonly TicketEloquentModel $ticket,
        public readonly string $requesterName,
        public readonly bool $isFromPortal = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return InboxPayload::forTicket(
            InboxKindEnum::NewTicket,
            $this->ticket,
            null,
            $this->isFromPortal ? __('raised it in the portal') : __('raised it by email'),
            $this->ticket->description === null ? null : Str::limit($this->ticket->description, 140),
            actorName: $this->requesterName,
        );
    }
}
