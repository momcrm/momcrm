<?php

declare(strict_types=1);

namespace App\Invoice\Controllers;

use App\Customer\Actions\FindCustomer;
use App\Invoice\InvoiceFormOptions;
use App\Invoice\PaymentTerms;
use App\Team\Actions\FindTeam;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The choices for the new-invoice dialog, fetched when it opens: customers,
 * their active services, and the dates and tax rate a new invoice starts
 * with. Named a customer, it is due by that customer's payment terms.
 */
final class ListInvoiceFormOptionsController
{
    public function __invoke(Request $request): JsonResponse
    {
        $today = CarbonImmutable::today();
        $teamId = (string) $request->user()?->current_team_id;
        $customer = FindCustomer::byIdForTeam($request->string('customer')->toString() ?: null, $teamId);
        $team = FindTeam::byId($teamId);

        return response()->json([
            ...InvoiceFormOptions::forTeam($teamId),
            'defaults' => [
                'issued_on' => $today->toDateString(),
                'due_on' => PaymentTerms::dueOn($today, $customer, $team)->toDateString(),
                'tax_rate_basis_points' => 1000,
            ],
        ]);
    }
}
