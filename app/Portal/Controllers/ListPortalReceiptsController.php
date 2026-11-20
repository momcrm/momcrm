<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\Payment\Payment;
use App\Portal\Actions\FindPortalBilling;
use App\Portal\PortalAccess;
use App\Portal\PortalBillingView;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's payments, each with its receipt.
 */
final class ListPortalReceiptsController
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(): Response
    {
        PortalAccess::authorizeSection(AccessResourceEnum::Payment);

        return Inertia::render('portal/receipts', [
            'receipts' => PortalAccess::visible(AccessResourceEnum::Payment, FindPortalBilling::receipts())
                ->map(fn (Payment $payment): array => PortalBillingView::receipt($payment))
                ->values()
                ->all(),
        ]);
    }
}
