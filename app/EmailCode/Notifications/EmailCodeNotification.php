<?php

declare(strict_types=1);

namespace App\EmailCode\Notifications;

use App\EmailCode\Actions\CreateEmailCode;
use App\EmailCode\EmailCodePurposeEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emails a short-lived code that proves the person owns the address.
 */
final class EmailCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $code,
        public readonly EmailCodePurposeEnum $purpose,
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
        $minutes = CreateEmailCode::MINUTES_VALID;

        return new MailMessage()
            ->subject($this->purpose === EmailCodePurposeEnum::PortalSignIn
                ? __('Your customer portal code is :code', ['code' => $this->code])
                : __('Your MomCRM code is :code', ['code' => $this->code]))
            ->greeting(__('Your code is :code', ['code' => $this->code]))
            ->line(match ($this->purpose) {
                EmailCodePurposeEnum::Confirm => __('Enter it in MomCRM to confirm it\'s you.'),
                EmailCodePurposeEnum::PortalSignIn => __('Enter it on the customer portal sign-in page to continue.'),
                EmailCodePurposeEnum::SignIn => __('Enter it in MomCRM to continue.'),
            })
            ->line(__('It works for :minutes minutes and only once.', ['minutes' => $minutes]))
            ->line(__("Didn't ask for a code? You can ignore this email. Nobody can sign in without it."));
    }
}
