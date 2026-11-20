<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Customer\CustomerWorkspace;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Invoice\Actions\FindInvoice;
use App\Invoice\Invoice;
use App\Invoice\InvoiceStatusEnum;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's invoices tab, optionally with one status.
 */
final class ListCustomerInvoicesController
{
    /**
     * @throws CouldNotFindCustomer
     */
    public function __invoke(Request $request, string $customer): Response
    {
        $workspace = CustomerWorkspace::open($request, $customer);
        $status = InvoiceStatusEnum::tryFrom($request->string('status')->toString());

        return Inertia::render('customers/workspace/invoices', [
            ...$workspace->props($request),
            'invoices' => FindInvoice::paginateForTeam($workspace->teamId, $status, customerId: $workspace->customerId())
                ->through(fn (Invoice $invoice): array => FindInvoice::listItem($invoice)),
            'filters' => ['status' => $status?->value],
            'statuses' => InvoiceStatusEnum::options(),
        ]);
    }
}
