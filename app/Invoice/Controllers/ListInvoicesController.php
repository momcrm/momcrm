<?php

declare(strict_types=1);

namespace App\Invoice\Controllers;

use App\Invoice\Actions\FindInvoice;
use App\Invoice\Invoice;
use App\Invoice\InvoiceStatusEnum;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ListInvoicesController
{
    public function __invoke(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString() ?: null;
        $status = InvoiceStatusEnum::tryFrom($request->string('status')->toString());

        return Inertia::render('invoices/index', [
            'invoices' => FindInvoice::paginateForTeam((string) $request->user()?->current_team_id, $status, $search)
                ->through(fn (Invoice $invoice): array => FindInvoice::listItem($invoice)),
            'filters' => ['search' => $search, 'status' => $status?->value],
            'statuses' => InvoiceStatusEnum::options(),
        ]);
    }
}
