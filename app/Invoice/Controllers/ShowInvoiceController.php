<?php

declare(strict_types=1);

namespace App\Invoice\Controllers;

use App\Customer\Actions\FindCustomer;
use App\Invoice\Actions\FindInvoice;
use App\Invoice\Exceptions\CouldNotFindInvoice;
use App\Invoice\InvoiceTotals;
use App\InvoiceItem\InvoiceItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShowInvoiceController
{
    /**
     * @throws CouldNotFindInvoice
     */
    public function __invoke(Request $request, string $invoice): Response
    {
        $teamId = (string) $request->user()?->current_team_id;
        $found = FindInvoice::byIdForTeam($invoice, $teamId);

        if ($found->isNull()) {
            throw CouldNotFindInvoice::notFound();
        }

        $items = FindInvoice::itemsFor($found);

        return Inertia::render('invoices/show', [
            'invoice' => $found->toSummary(),
            'items' => $items->map(fn (InvoiceItem $item): array => $item->toSummary())->values()->all(),
            'totals' => InvoiceTotals::fromItems($items, $found->getTaxRateBasisPoints()->toInteger())->toArray(),
            'customer' => FindCustomer::byIdForTeam($found->getCustomerId()->toString(), $teamId)->toSummary(),
        ]);
    }
}
