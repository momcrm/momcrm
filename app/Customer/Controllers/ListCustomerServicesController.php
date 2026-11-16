<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Customer\CustomerWorkspace;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Service\Actions\FindService;
use App\Service\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's services tab.
 */
final class ListCustomerServicesController
{
    /**
     * @throws CouldNotFindCustomer
     */
    public function __invoke(Request $request, string $customer): Response
    {
        $workspace = CustomerWorkspace::open($request, $customer);

        return Inertia::render('customers/workspace/services', [
            ...$workspace->props($request),
            'services' => FindService::forCustomer($workspace->customerId(), $workspace->teamId)
                ->map(fn (Service $service): array => $service->toSummary())
                ->values()
                ->all(),
        ]);
    }
}
