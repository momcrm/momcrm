<?php

declare(strict_types=1);

namespace App\Comment\Notifications;

use App\Inbox\InboxKindEnum;
use App\Inbox\InboxPayload;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Tells a ticket's watchers someone commented on it.
 */
final class TicketCommentedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly TicketEloquentModel $ticket,
        public readonly ?UserEloquentModel $actor,
        public readonly string $excerpt,
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
        return InboxPayload::forTicket(InboxKindEnum::Comment, $this->ticket, $this->actor, __('commented'), $this->excerpt);
    }
}
