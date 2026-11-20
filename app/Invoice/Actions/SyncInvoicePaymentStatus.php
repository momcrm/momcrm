<?php

declare(strict_types=1);

namespace App\Invoice\Actions;

use App\Invoice\Invoice;
use App\Invoice\InvoiceStatusEnum;
use Carbon\CarbonImmutable;

/**
 * Keeps an invoice's status in step with the payments applied to it: a sent
 * invoice with nothing left owing becomes paid, and a paid invoice that is
 * owed again (a payment was removed) goes back to sent.
 */
final readonly class SyncInvoicePaymentStatus
{
    /**
     * @param  array<int, string>  $invoiceIds
     */
    public function __construct(
        private array $invoiceIds,
        private string $teamId,
    ) {}

    public function __invoke(): void
    {
        foreach (FindInvoice::byIdsForTeam(array_values(array_unique($this->invoiceIds)), $this->teamId) as $invoice) {
            $this->sync($invoice);
        }
    }

    /**
     * @param  array<int, string>  $invoiceIds
     */
    public static function forInvoices(array $invoiceIds, string $teamId): void
    {
        new self($invoiceIds, $teamId)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }

    private function sync(Invoice $invoice): void
    {
        $status = $invoice->getStatus()->toEnum();
        $isOwed = $invoice->balanceAfter($invoice->totals()) > 0;

        if ($status === InvoiceStatusEnum::Sent && ! $isOwed) {
            UpdateInvoice::fromInvoice($invoice->setStatus(InvoiceStatusEnum::Paid)->setPaidAt(CarbonImmutable::now()));
        }

        if ($status === InvoiceStatusEnum::Paid && $isOwed) {
            UpdateInvoice::fromInvoice($invoice->setStatus(InvoiceStatusEnum::Sent)->setPaidAt(null));
        }
    }
}
