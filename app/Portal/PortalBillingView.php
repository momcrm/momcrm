<?php

declare(strict_types=1);

namespace App\Portal;

use App\Invoice\Invoice;
use App\Payment\Payment;

/**
 * How invoices and payments look to a contact in the portal: what they were
 * billed and paid, never staff notes or custom fields.
 */
final class PortalBillingView
{
    /**
     * Expects the invoice to have been found with its items.
     *
     * @return array{id: string, reference: string, issued_on: ?string, due_on: ?string, status: string, is_overdue: bool, currency: string, total_in_cents: int, balance_in_cents: int, pdf_url: string}
     */
    public static function invoice(Invoice $invoice): array
    {
        $totals = $invoice->totals();

        return [
            'id' => $invoice->getId()->toString(),
            'reference' => $invoice->getReference(),
            'issued_on' => $invoice->getIssuedOn()->toNullableCarbon()?->toDateString(),
            'due_on' => $invoice->getDueOn()->toNullableCarbon()?->toDateString(),
            'status' => $invoice->getStatus()->toString(),
            'is_overdue' => $invoice->isOverdue(),
            'currency' => $invoice->getCurrency()->toEnum()->value,
            'total_in_cents' => $totals->toArray()['total_in_cents'],
            'balance_in_cents' => $invoice->balanceAfter($totals),
            'pdf_url' => route('portal.invoices.pdf', $invoice->getId()->toString()),
        ];
    }

    /**
     * @return array{id: string, reference: string, paid_on: ?string, method: string, currency: string, amount_in_cents: int, pdf_url: string}
     */
    public static function receipt(Payment $payment): array
    {
        return [
            'id' => $payment->getId()->toString(),
            'reference' => $payment->getReferenceNumber(),
            'paid_on' => $payment->getPaidOn()->toNullableCarbon()?->toDateString(),
            'method' => $payment->getMethod()->toEnum()->label(),
            'currency' => $payment->getCurrency()->toEnum()->value,
            'amount_in_cents' => $payment->getAmountInCents()->toInteger(),
            'pdf_url' => route('portal.receipts.pdf', $payment->getId()->toString()),
        ];
    }
}
