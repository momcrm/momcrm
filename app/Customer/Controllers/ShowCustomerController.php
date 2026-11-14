<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Asset\Actions\FindAsset;
use App\Asset\Asset;
use App\Contact\Actions\FindContact;
use App\Contact\Contact;
use App\Customer\Actions\FindCustomer;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Location\Actions\FindLocation;
use App\Location\Location;
use App\Service\Actions\FindService;
use App\Service\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShowCustomerController
{
    /**
     * @throws CouldNotFindCustomer
     */
    public function __invoke(Request $request, string $customer): Response
    {
        $teamId = (string) $request->user()?->current_team_id;
        $found = FindCustomer::byIdForTeam($customer, $teamId);

        if ($found->isNull()) {
            throw CouldNotFindCustomer::notFound();
        }

        $customerId = $found->getId()->toString();

        return Inertia::render('customers/show', [
            'customer' => $found->toSummary(),
            'contacts' => FindContact::forCustomer($customerId, $teamId)
                ->map(fn (Contact $contact): array => $contact->toSummary())
                ->values()
                ->all(),
            'locations' => FindLocation::forCustomer($customerId, $teamId)
                ->map(fn (Location $location): array => $location->toSummary())
                ->values()
                ->all(),
            'assets' => FindAsset::forCustomer($customerId, $teamId)
                ->map(fn (Asset $asset): array => $asset->toSummary())
                ->values()
                ->all(),
            'services' => FindService::forCustomer($customerId, $teamId)
                ->map(fn (Service $service): array => $service->toSummary())
                ->values()
                ->all(),
        ]);
    }
}
