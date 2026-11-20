<?php

declare(strict_types=1);

namespace App\Invoice\Controllers;

use App\Customer\Actions\FindCustomer;
use App\Invoice\InvoiceFormOptions;
use App\Invoice\PaymentTerms;
use App\Team\Actions\FindTeam;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CreateInvoiceController
{
    public function __invoke(Request $request): Response
    {
        $today = CarbonImmutable::today();
        $teamId = (string) $request->user()?->current_team_id;
        $customer = FindCustomer::byIdForTeam($request->string('customer')->toString() ?: null, $teamId);
        $team = FindTeam::byId($teamId);

        return Inertia::render('invoices/create', [
            ...InvoiceFormOptions::forTeam($teamId),
            'customerId' => $request->string('customer')->toString() ?: null,
            'defaults' => [
                'issued_on' => $today->toDateString(),
                'due_on' => PaymentTerms::dueOn($today, $customer, $team)->toDateString(),
                'tax_rate_basis_points' => 1000,
            ],
        ]);
    }
}
