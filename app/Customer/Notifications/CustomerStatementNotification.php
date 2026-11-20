<?php

declare(strict_types=1);

namespace App\Customer\Notifications;

use App\Customer\CustomerStatement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sends a customer their statement for a period, with the PDF attached.
 */
final class CustomerStatementNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly CustomerStatement $statement,
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
        $team = $this->statement->team;
        $closing = $this->statement->closingBalanceInCents;

        $message = new MailMessage()
            ->subject(__('Statement from :team, :from to :to', [
                'team' => $team->getNameOnInvoices(),
                'from' => $this->statement->from->format('j M Y'),
                'to' => $this->statement->to->format('j M Y'),
            ]))
            ->greeting(__('Your statement'))
            ->line(__('Here is the statement of :customer\'s account from :from to :to.', [
                'customer' => $this->statement->customer->getName()->toString(),
                'from' => $this->statement->from->format('j M Y'),
                'to' => $this->statement->to->format('j M Y'),
            ]))
            ->line(match (true) {
                $closing > 0 => __('The balance owing is :amount.', ['amount' => $this->statement->money($closing)]),
                $closing < 0 => __('You are :amount in credit.', ['amount' => $this->statement->money(-$closing)]),
                default => __('Nothing is owing.'),
            });

        if ($team->getPaymentDetails()->isNotNull() && $closing > 0) {
            $message->line(__('How to pay: :details', ['details' => $team->getPaymentDetails()->toString()]));
        }

        if ($team->getBillingEmail()->isNotNull()) {
            $message->replyTo($team->getBillingEmail()->toString(), $team->getNameOnInvoices());
        }

        return $message
            ->line(__('The statement is attached.'))
            ->salutation(__('Thanks, :team', ['team' => $team->getNameOnInvoices()]))
            ->attachData(
                Pdf::loadView('customers.statement', ['statement' => $this->statement])->setPaper('a4')->output(),
                $this->statement->fileName(),
                ['mime' => 'application/pdf'],
            );
    }
}
