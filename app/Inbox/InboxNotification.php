<?php

declare(strict_types=1);

namespace App\Inbox;

use App\Shared\Types;
use App\User\UserEloquentModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A notification shown in the inbox. It is emailed too when the person
 * chose to be emailed about its kind.
 */
abstract class InboxNotification extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    /**
     * The inbox payload, built with InboxPayload.
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(object $notifiable): array;

    /**
     * @return array<int, string>
     */
    final public function via(object $notifiable): array
    {
        $kind = InboxKindEnum::tryFrom(Types::string($this->toArray($notifiable)['kind'] ?? ''));
        $isEmailed = $notifiable instanceof UserEloquentModel && $kind !== null && NotificationEmails::isWantedBy($notifiable, $kind);

        return $isEmailed ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return InboxMail::fromPayload($this->toArray($notifiable));
    }
}
