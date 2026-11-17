<?php

declare(strict_types=1);

namespace App\Invoice\Controllers;

use App\Customer\Actions\FindCustomer;
use App\CustomField\CustomFieldRelationLabels;
use App\CustomField\CustomFieldTargetEnum;
use App\Invoice\Actions\FindInvoice;
use App\Invoice\Exceptions\CouldNotFindInvoice;
use App\Invoice\InvoiceFormOptions;
use App\InvoiceItem\InvoiceItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class EditInvoiceController
{
    /**
     * @throws CouldNotFindInvoice
     */
    public function __invoke(Request $request, string $invoice): Response|RedirectResponse
    {
        $teamId = (string) $request->user()?->current_team_id;
        $found = FindInvoice::byIdForTeam($invoice, $teamId);

        if ($found->isNull()) {
            throw CouldNotFindInvoice::notFound();
        }

        if (! $found->isDraft()) {
            Inertia::flash('toast', ['type' => 'info', 'message' => __('Only draft invoices can be edited.')]);

            return to_route('invoices.show', $found->getId()->toString());
        }

        return Inertia::render('invoices/edit', [
            ...InvoiceFormOptions::forTeam($teamId),
            'invoice' => $found->toSummary(),
            'customFieldLabels' => CustomFieldRelationLabels::for($teamId, CustomFieldTargetEnum::Invoice, $found->getCustomFields()->toValues()),
            'items' => FindInvoice::itemsFor($found)->map(fn (InvoiceItem $item): array => $item->toSummary())->values()->all(),
            'customer' => FindCustomer::byIdForTeam($found->getCustomerId()->toString(), $teamId)->toSummary(),
        ]);
    }
}
