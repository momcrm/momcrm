<?php

declare(strict_types=1);

namespace App\Ticket\Notifications;

use App\Inbox\InboxKindEnum;
use App\Inbox\InboxNotification;
use App\Inbox\InboxPayload;
use App\Ticket\TicketEloquentModel;

/**
 * Reminds the assignee that an open ticket is due tomorrow.
 */
final class TicketDueSoonNotification extends InboxNotification
{
    public function __construct(
        public readonly TicketEloquentModel $ticket,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return InboxPayload::forTicket(InboxKindEnum::Update, $this->ticket, null, __('due tomorrow'));
    }
}
