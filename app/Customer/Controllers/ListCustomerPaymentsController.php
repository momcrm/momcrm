<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Customer\CustomerWorkspace;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Payment\Actions\FindPayment;
use App\Payment\Payment;
use App\Payment\PaymentFilters;
use App\Payment\PaymentMethodEnum;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's payments tab.
 */
final class ListCustomerPaymentsController
{
    /**
     * @throws CouldNotFindCustomer
     */
    public function __invoke(Request $request, string $customer): Response
    {
        $workspace = CustomerWorkspace::open($request, $customer);
        $filters = PaymentFilters::fromRequest($request, $workspace->customerId());

        return Inertia::render('customers/workspace/payments', [
            ...$workspace->props($request),
            'payments' => FindPayment::paginateForTeam($workspace->teamId, $filters)
                ->through(fn (Payment $payment): array => $payment->toSummary()),
            'filters' => $filters->toArray(),
            'methods' => PaymentMethodEnum::options(),
        ]);
    }
}
