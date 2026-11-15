<?php

declare(strict_types=1);

namespace App\Invoice;

use App\InvoiceItem\InvoiceItem;
use App\Shared\Types;
use Illuminate\Support\Collection;

/**
 * An invoice's subtotal, tax and total in cents. Tax is the invoice's rate
 * applied once to the subtotal and rounded to the nearest cent.
 */
final readonly class InvoiceTotals
{
    public function __construct(
        public int $subtotalInCents,
        public int $taxInCents,
        public int $totalInCents,
    ) {}

    /**
     * @param  Collection<int, InvoiceItem>  $items
     */
    public static function fromItems(Collection $items, int $taxRateBasisPoints): self
    {
        return self::fromSubtotal(Types::intOrZero($items->sum(fn (InvoiceItem $item): int => $item->getLineTotalInCents())), $taxRateBasisPoints);
    }

    public static function fromSubtotal(int $subtotalInCents, int $taxRateBasisPoints): self
    {
        $tax = (int) round($subtotalInCents * $taxRateBasisPoints / 10000);

        return new self($subtotalInCents, $tax, $subtotalInCents + $tax);
    }

    /**
     * @return array{subtotal_in_cents: int, tax_in_cents: int, total_in_cents: int}
     */
    public function toArray(): array
    {
        return [
            'subtotal_in_cents' => $this->subtotalInCents,
            'tax_in_cents' => $this->taxInCents,
            'total_in_cents' => $this->totalInCents,
        ];
    }
}
