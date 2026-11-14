<?php

declare(strict_types=1);

namespace App\User\Notifications;

use App\User\UserEloquentModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent once a new user has confirmed their email address.
 */
final class WelcomeNotification extends Notification
{
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(UserEloquentModel $notifiable): MailMessage
    {
        return new MailMessage()
            ->subject(__("You're in — here's where to start"))
            ->greeting(__('Hi :name,', ['name' => $notifiable->name]))
            ->line(__('Welcome to MomCRM. Your workspace is ready.'))
            ->line(__("The fastest way to see what it can do: add a customer and their main contact, then open a ticket for something you're working on."))
            ->action(__('Open my dashboard'), route('dashboard'))
            ->salutation(__('— The MomCRM team. Reply to this email if you get stuck; a real person reads it.'));
    }
}
