<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Asset\Actions\FindAsset;
use App\Asset\Asset;
use App\Customer\CustomerWorkspace;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Location\Actions\FindLocation;
use App\Location\Location;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's assets tab, with where each one is.
 */
final class ListCustomerAssetsController
{
    /**
     * @throws CouldNotFindCustomer
     */
    public function __invoke(Request $request, string $customer): Response
    {
        $workspace = CustomerWorkspace::open($request, $customer);

        return Inertia::render('customers/workspace/assets', [
            ...$workspace->props($request),
            'assets' => FindAsset::forCustomer($workspace->customerId(), $workspace->teamId)
                ->map(fn (Asset $asset): array => [...$asset->toSummary(), 'labels' => $asset->getLabels()->toDataCollection()->toTags()])
                ->values()
                ->all(),
            'locations' => FindLocation::forCustomer($workspace->customerId(), $workspace->teamId)
                ->map(fn (Location $location): array => $location->toSummary())
                ->values()
                ->all(),
        ]);
    }
}
