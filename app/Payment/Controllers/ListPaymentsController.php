<?php

declare(strict_types=1);

namespace App\Payment\Controllers;

use App\Payment\Actions\FindPayment;
use App\Payment\Payment;
use App\Payment\PaymentFilters;
use App\Payment\PaymentMethodEnum;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Every payment the team has received, as Amphora's Receipts list: search,
 * filter by method and date paid, and sort.
 */
final class ListPaymentsController
{
    public function __invoke(Request $request): Response
    {
        $filters = PaymentFilters::fromRequest($request);

        return Inertia::render('payments/index', [
            'payments' => FindPayment::paginateForTeam((string) $request->user()?->current_team_id, $filters)
                ->through(fn (Payment $payment): array => $payment->toSummary()),
            'filters' => $filters->toArray(),
            'methods' => PaymentMethodEnum::options(),
        ]);
    }
}
