<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\Invoice\Invoice;
use App\Portal\Actions\FindPortalBilling;
use App\Portal\PortalAccess;
use App\Portal\PortalBillingView;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's invoices, with what is still owing on each.
 */
final class ListPortalInvoicesController
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(): Response
    {
        PortalAccess::authorizeSection(AccessResourceEnum::Invoice);

        return Inertia::render('portal/invoices', [
            'invoices' => PortalAccess::visible(AccessResourceEnum::Invoice, FindPortalBilling::invoices())
                ->map(fn (Invoice $invoice): array => PortalBillingView::invoice($invoice))
                ->values()
                ->all(),
        ]);
    }
}
