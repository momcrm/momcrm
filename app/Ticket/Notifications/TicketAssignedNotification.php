<?php

declare(strict_types=1);

namespace App\Ticket\Notifications;

use App\Inbox\InboxKindEnum;
use App\Inbox\InboxPayload;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Tells someone a ticket was assigned to them.
 */
final class TicketAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly TicketEloquentModel $ticket,
        public readonly ?UserEloquentModel $actor,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return InboxPayload::forTicket(InboxKindEnum::Assignment, $this->ticket, $this->actor, __('assigned you'));
    }
}
