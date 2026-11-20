<?php

declare(strict_types=1);

namespace App\Payment\Notifications;

use App\Payment\PaymentDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Thanks a customer for a payment, with its receipt attached, as Amphora's
 * receipt email does: what was paid and the invoices it went to.
 */
final class PaymentReceiptNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly PaymentDocument $document,
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
        $payment = $this->document->payment;
        $team = $this->document->team;

        $message = new MailMessage()
            ->subject(__('Receipt :reference from :team', ['reference' => $payment->getReferenceNumber(), 'team' => $team->getNameOnInvoices()]))
            ->greeting(__('Thank you for your payment'))
            ->line(__('We received :amount from :customer on :date.', [
                'amount' => $this->document->money($payment->getAmountInCents()->toInteger()),
                'customer' => $this->document->customer->getName()->toString(),
                'date' => $payment->getPaidOn()->toCarbon()->format('j M Y'),
            ]));

        foreach ($this->document->allocations as $allocation) {
            $message->line(__(':amount paid invoice :reference.', [
                'amount' => $this->document->money($allocation->getAmountInCents()->toInteger()),
                'reference' => $allocation->getInvoice()->toNullableData()?->getReference(),
            ]));
        }

        if ($payment->getUnallocatedInCents() > 0) {
            $message->line(__(':amount is kept as credit for your next invoices.', ['amount' => $this->document->money($payment->getUnallocatedInCents())]));
        }

        if ($team->getBillingEmail()->isNotNull()) {
            $message->replyTo($team->getBillingEmail()->toString(), $team->getNameOnInvoices());
        }

        return $message
            ->line(__('Your receipt is attached.'))
            ->salutation(__('Thanks, :team', ['team' => $team->getNameOnInvoices()]))
            ->attachData(
                Pdf::loadView('payments.receipt', ['document' => $this->document])->setPaper('a4')->output(),
                $this->document->fileName(),
                ['mime' => 'application/pdf'],
            );
    }
}
