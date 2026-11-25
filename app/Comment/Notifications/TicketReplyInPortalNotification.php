<?php

declare(strict_types=1);

namespace App\Comment\Notifications;

use App\Shared\BrandedMailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a customer's contact that staff replied on their ticket, with the
 * reply and a link to it in the customer portal. Used when the team has no
 * mailbox to send the reply itself from.
 */
final class TicketReplyInPortalNotification extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(
        public readonly string $ticketId,
        public readonly int $number,
        public readonly string $title,
        public readonly string $teamName,
        public readonly string $authorName,
        public readonly string $body,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return new BrandedMailMessage()
            ->sentBy($this->teamName)
            ->subject(__('Re: [#:number] :title', ['number' => $this->number, 'title' => $this->title]))
            ->greeting(__(':name replied to your ticket', ['name' => $this->authorName]))
            ->line($this->body)
            ->action(__('View the ticket'), route('tickets.show', $this->ticketId))
            ->line(__('Reply in the customer portal to keep the conversation going.'))
            ->salutation(__('Thanks, :team', ['team' => $this->teamName]));
    }
}
