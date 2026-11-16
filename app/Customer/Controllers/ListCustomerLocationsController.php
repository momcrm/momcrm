<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Customer\CustomerWorkspace;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Location\Actions\FindLocation;
use App\Location\Location;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's locations tab.
 */
final class ListCustomerLocationsController
{
    /**
     * @throws CouldNotFindCustomer
     */
    public function __invoke(Request $request, string $customer): Response
    {
        $workspace = CustomerWorkspace::open($request, $customer);

        return Inertia::render('customers/workspace/locations', [
            ...$workspace->props($request),
            'locations' => FindLocation::forCustomer($workspace->customerId(), $workspace->teamId)
                ->map(fn (Location $location): array => $location->toSummary())
                ->values()
                ->all(),
        ]);
    }
}
