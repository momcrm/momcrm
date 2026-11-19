<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Customer\CustomerWorkspace;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Service\Actions\FindService;
use App\Service\Service;
use App\Service\ServiceFormOptions;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's services tab, with what the Add service dialog offers.
 * Archived services are left out unless `?archived=1` asks for them.
 */
final class ListCustomerServicesController
{
    /**
     * @throws CouldNotFindCustomer
     */
    public function __invoke(Request $request, string $customer): Response
    {
        $workspace = CustomerWorkspace::open($request, $customer);
        $services = FindService::forCustomer($workspace->customerId(), $workspace->teamId);
        $showsArchived = $request->boolean('archived');

        return Inertia::render('customers/workspace/services', [
            ...$workspace->props($request),
            'services' => ($showsArchived ? $services : $services->withoutArchived())->toSummaries(),
            'archivedCount' => $services->filter(fn (Service $service): bool => $service->isArchived())->count(),
            'showsArchived' => $showsArchived,
            'serviceForm' => ServiceFormOptions::for($workspace->customerId(), $workspace->teamId),
        ]);
    }
}
