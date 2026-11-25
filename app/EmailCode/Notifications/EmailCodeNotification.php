<?php

declare(strict_types=1);

namespace App\EmailCode\Notifications;

use App\EmailCode\Actions\CreateEmailCode;
use App\EmailCode\EmailCodePurposeEnum;
use App\Shared\BrandedMailMessage;
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

        return new BrandedMailMessage()
            ->subject(__('Your MomCRM code is :code', ['code' => $this->code]))
            ->greeting(match ($this->purpose) {
                EmailCodePurposeEnum::Confirm => __('Confirm it\'s you'),
                EmailCodePurposeEnum::SignIn => __('Your sign-in code'),
            })
            ->highlight(
                __('Your code'),
                $this->code,
                __('Works for :minutes minutes, and only once.', ['minutes' => $minutes]),
                isCode: true,
            )
            ->line(match ($this->purpose) {
                EmailCodePurposeEnum::Confirm => __('Enter it in MomCRM to confirm it\'s you.'),
                EmailCodePurposeEnum::SignIn => __('Enter it in MomCRM to continue.'),
            })
            ->line(__("Didn't ask for a code? You can ignore this email. Nobody can sign in without it."));
    }
}
