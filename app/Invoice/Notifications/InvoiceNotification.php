<?php

declare(strict_types=1);

namespace App\Invoice\Notifications;

use App\Invoice\InvoiceDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * An invoice for the customer, with its PDF attached: what is owed, by when,
 * and how to pay. Replies go to the team's billing email.
 */
final class InvoiceNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly InvoiceDocument $document,
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
        $invoice = $this->document->invoice;
        $team = $this->document->team;
        $owing = $invoice->balanceAfter($this->document->totals);

        $message = new MailMessage()
            ->subject(__('Invoice :reference from :team', ['reference' => $invoice->getReference(), 'team' => $team->getNameOnInvoices()]))
            ->greeting(__('Hello :customer', ['customer' => $this->document->customer->getName()->toString()]))
            ->line($owing > 0
                ? __('Invoice :reference for :amount is due on :date.', [
                    'reference' => $invoice->getReference(),
                    'amount' => $this->document->money($owing),
                    'date' => $invoice->getDueOn()->toCarbon()->format('j M Y'),
                ])
                : __('Invoice :reference is paid. Thank you.', ['reference' => $invoice->getReference()]));

        $bpay = $this->document->bpay();

        if ($owing > 0 && $bpay !== null) {
            $message->line(__('Pay by BPAY: biller code :code, reference :reference.', ['code' => $bpay['biller_code'], 'reference' => $bpay['reference']]));
        }

        if ($owing > 0 && $team->getPaymentDetails()->isNotNull()) {
            $message->line($team->getPaymentDetails()->toString());
        }

        if ($team->getBillingEmail()->isNotNull()) {
            $message->replyTo($team->getBillingEmail()->toString(), $team->getNameOnInvoices());
        }

        return $message
            ->line(__('The invoice is attached.'))
            ->salutation(__('Thanks, :team', ['team' => $team->getNameOnInvoices()]))
            ->attachData(
                Pdf::loadView('invoices.pdf', ['document' => $this->document])->setPaper('a4')->output(),
                $this->document->fileName(),
                ['mime' => 'application/pdf'],
            );
    }
}
