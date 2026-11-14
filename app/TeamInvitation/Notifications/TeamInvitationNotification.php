<?php

declare(strict_types=1);

namespace App\TeamInvitation\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Invites someone, by email address, to join a team.
 */
final class TeamInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $teamName,
        public readonly string $inviterName,
        public readonly string $acceptUrl,
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
        return new MailMessage()
            ->subject(__(':inviter invited you to :team on MomCRM', ['inviter' => $this->inviterName, 'team' => $this->teamName]))
            ->line(__(':inviter wants you to work on customers and tickets together in :team.', ['inviter' => $this->inviterName, 'team' => $this->teamName]))
            ->action(__('Join :team', ['team' => $this->teamName]), $this->acceptUrl)
            ->line(__('This invitation expires in 7 days. Not expecting it? You can ignore this email.'));
    }
}
