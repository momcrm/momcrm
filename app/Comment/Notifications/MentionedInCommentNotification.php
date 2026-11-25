<?php

declare(strict_types=1);

namespace App\Comment\Notifications;

use App\Inbox\InboxKindEnum;
use App\Inbox\InboxNotification;
use App\Inbox\InboxPayload;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

/**
 * Tells someone they were @mentioned in a comment.
 */
final class MentionedInCommentNotification extends InboxNotification
{
    public function __construct(
        public readonly TicketEloquentModel $ticket,
        public readonly ?UserEloquentModel $actor,
        public readonly string $excerpt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return InboxPayload::forTicket(InboxKindEnum::Mention, $this->ticket, $this->actor, __('mentioned you'), $this->excerpt);
    }
}
