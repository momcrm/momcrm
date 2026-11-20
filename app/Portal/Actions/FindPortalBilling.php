<?php

declare(strict_types=1);

namespace App\Portal\Actions;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\Invoice\Actions\FindInvoice;
use App\Invoice\Invoice;
use App\Invoice\InvoiceCollection;
use App\Invoice\InvoiceStatusEnum;
use App\Payment\Actions\FindPayment;
use App\Payment\Payment;
use App\Payment\PaymentCollection;
use App\Portal\PortalAccess;
use App\Portal\PortalContact;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The contact's customer's invoices and payments in the portal. Only invoices
 * that were sent are theirs to see: drafts and voided ones never show.
 */
final class FindPortalBilling
{
    /**
     * Sent and paid invoices, newest first.
     */
    public static function invoices(): InvoiceCollection
    {
        $contact = PortalContact::current();

        return FindInvoice::billedToCustomer($contact->getCustomerId()->toString(), $contact->getTeamId()->toString())->reverse()->values();
    }

    /**
     * Payments, newest first.
     */
    public static function receipts(): PaymentCollection
    {
        $contact = PortalContact::current();

        return FindPayment::forCustomer($contact->getCustomerId()->toString(), $contact->getTeamId()->toString())->reverse()->values();
    }

    /**
     * One of the customer's sent or paid invoices that the contact may see;
     * anything else is not found.
     */
    public static function visibleInvoice(?string $id): Invoice
    {
        $contact = PortalContact::current();
        $invoice = FindInvoice::byIdForTeam($id, $contact->getTeamId()->toString());

        $isTheirs = $invoice->isNotNull()
            && $invoice->getCustomerId()->equalsTo($contact->getCustomerId()->toString())
            && in_array($invoice->getStatus()->toString(), [InvoiceStatusEnum::Sent->value, InvoiceStatusEnum::Paid->value], true);

        if (! $isTheirs || ! PortalAccess::allows(AccessResourceEnum::Invoice, AccessActionEnum::View, $invoice)) {
            throw new NotFoundHttpException;
        }

        return $invoice;
    }

    /**
     * One of the customer's payments that the contact may see; anything else
     * is not found.
     */
    public static function visibleReceipt(?string $id): Payment
    {
        $contact = PortalContact::current();
        $payment = FindPayment::byIdForTeam($id, $contact->getTeamId()->toString());

        if ($payment->isNull()
            || ! $payment->getCustomerId()->equalsTo($contact->getCustomerId()->toString())
            || ! PortalAccess::allows(AccessResourceEnum::Payment, AccessActionEnum::View, $payment)) {
            throw new NotFoundHttpException;
        }

        return $payment;
    }
}
