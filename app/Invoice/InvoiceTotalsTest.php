<?php

declare(strict_types=1);

use App\Invoice\InvoiceTotals;
use App\InvoiceItem\InvoiceItem;

it('adds the lines and applies the tax rate once to the subtotal', function (): void {
    $items = collect([
        InvoiceItem::new()->setQuantity(3)->setUnitPriceInCents(4995),
        InvoiceItem::new()->setQuantity(1)->setUnitPriceInCents(1),
    ]);

    $totals = InvoiceTotals::fromItems($items, taxRateBasisPoints: 1000);

    expect($totals->toArray())->toBe([
        'subtotal_in_cents' => 14986,
        'tax_in_cents' => 1499,
        'total_in_cents' => 16485,
    ]);
});
