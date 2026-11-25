<?php

declare(strict_types=1);

namespace App\Ticket\Notifications;

use App\Inbox\InboxKindEnum;
use App\Inbox\InboxNotification;
use App\Inbox\InboxPayload;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

/**
 * Tells a ticket's watchers its status changed.
 */
final class TicketStatusChangedNotification extends InboxNotification
{
    public function __construct(
        public readonly TicketEloquentModel $ticket,
        public readonly ?UserEloquentModel $actor,
        public readonly string $statusLabel,
        public readonly ?string $actorName = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return InboxPayload::forTicket(InboxKindEnum::Update, $this->ticket, $this->actor, __('moved it to :status', ['status' => $this->statusLabel]), actorName: $this->actorName);
    }
}
