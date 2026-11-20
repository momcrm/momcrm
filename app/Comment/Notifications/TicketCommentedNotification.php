<?php

declare(strict_types=1);

namespace App\Comment\Notifications;

use App\Inbox\InboxKindEnum;
use App\Inbox\InboxPayload;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Notification;

/**
 * Tells a ticket's watchers someone commented on it, or that the customer
 * replied by email, in which case the sender is named as there is no actor.
 */
final class TicketCommentedNotification extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(
        public readonly TicketEloquentModel $ticket,
        public readonly ?UserEloquentModel $actor,
        public readonly string $excerpt,
        public readonly ?string $senderName = null,
        public readonly bool $isFromPortal = false,
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
        return InboxPayload::forTicket(
            InboxKindEnum::Comment,
            $this->ticket,
            $this->actor,
            match (true) {
                $this->senderName === null => __('commented'),
                $this->isFromPortal => __('replied in the portal'),
                default => __('replied by email'),
            },
            $this->excerpt,
            actorName: $this->senderName,
        );
    }
}
