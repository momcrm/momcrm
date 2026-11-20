<?php

declare(strict_types=1);

namespace App\Contact\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Invites a customer's contact to the customer portal, with a link to its
 * sign-in page filled in with their address.
 */
final class PortalInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $teamName,
        public readonly string $customerName,
        public readonly string $email,
        public readonly ?string $invitedByName = null,
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
            ->subject(__(':team has invited you to their customer portal', ['team' => $this->teamName]))
            ->greeting(__('Hi,'))
            ->line($this->invitedByName === null
                ? __(':team has given you access to their customer portal for :customer.', ['team' => $this->teamName, 'customer' => $this->customerName])
                : __(':name at :team has given you access to their customer portal for :customer.', ['name' => $this->invitedByName, 'team' => $this->teamName, 'customer' => $this->customerName]))
            ->line(__('There you can raise and follow tickets, and see what your access allows, such as invoices and services.'))
            ->action(__('Open the customer portal'), route('portal.login', ['email' => $this->email]))
            ->line(__("You sign in with a code we email you, so there's no password to remember."))
            ->salutation(__('Thanks, :team', ['team' => $this->teamName]));
    }
}
