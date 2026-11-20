<?php

declare(strict_types=1);

namespace App\Invoice;

use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\Invoice\Actions\FindInvoice;
use App\InvoiceItem\InvoiceItem;
use App\InvoiceItem\InvoiceItemCollection;
use App\Team\Actions\FindTeam;
use App\Team\Team;
use Illuminate\Support\Number;

/**
 * Everything printed on an invoice: its title, who it is from, who it bills,
 * its lines and its totals. The invoice page, the invoice dialog and the PDF
 * all show this, so they never disagree.
 */
final readonly class InvoiceDocument
{
    public function __construct(
        public Invoice $invoice,
        public InvoiceItemCollection $items,
        public InvoiceTotals $totals,
        public Customer $customer,
        public Team $team,
    ) {}

    public static function for(Invoice $invoice): self
    {
        $items = FindInvoice::itemsFor($invoice);
        $teamId = $invoice->getTeamId()->toString();

        return new self(
            $invoice,
            $items,
            InvoiceTotals::fromItems($items, $invoice->getTaxRateBasisPoints()->toInteger()),
            FindCustomer::byIdForTeam($invoice->getCustomerId()->toString(), $teamId),
            FindTeam::byId($teamId),
        );
    }

    /**
     * What the invoice is headed: a tax invoice when the team has a tax ID and
     * the invoice charges tax (a VAT invoice where the tax is VAT), otherwise
     * a plain invoice.
     */
    public function title(): string
    {
        if ($this->team->getTaxId()->isNull() || $this->invoice->getTaxRateBasisPoints()->toInteger() === 0) {
            return __('Invoice');
        }

        return str_starts_with(mb_strtoupper($this->team->getTaxIdLabel()->toString()), 'VAT') ? __('VAT invoice') : __('Tax invoice');
    }

    /**
     * The file name the PDF downloads as, e.g. INV-0042.pdf.
     */
    public function fileName(): string
    {
        return $this->invoice->getReference().'.pdf';
    }

    /**
     * An amount in the invoice's currency, e.g. A$1,250.00.
     */
    public function money(int $cents): string
    {
        return (string) Number::currency($cents / 100, $this->invoice->getCurrency()->toEnum()->value);
    }

    /**
     * @return array{title: string, invoice: array<string, mixed>, items: array<int, array<string, mixed>>, totals: array{subtotal_in_cents: int, tax_in_cents: int, total_in_cents: int}, customer: array<string, mixed>, from: array<string, ?string>}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title(),
            'invoice' => $this->invoice->toSummary(),
            'items' => $this->items->map(fn (InvoiceItem $item): array => $item->toSummary())->values()->all(),
            'totals' => $this->totals->toArray(),
            'customer' => $this->customer->toSummary(),
            'from' => $this->team->toBillingSummary(),
        ];
    }
}
