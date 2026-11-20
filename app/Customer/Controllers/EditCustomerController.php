<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Contact\Actions\FindContact;
use App\Contact\Contact;
use App\Customer\CustomerBillingTypeEnum;
use App\Customer\CustomerStatusEnum;
use App\Customer\CustomerWorkspace;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Location\Actions\FindLocation;
use App\Location\Location;
use App\Shared\CurrencyEnum;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's settings tab: its details, main contact and location, and
 * deleting it.
 */
final class EditCustomerController
{
    /**
     * @throws CouldNotFindCustomer
     */
    public function __invoke(Request $request, string $customer): Response
    {
        $workspace = CustomerWorkspace::open($request, $customer);
        $contacts = FindContact::forCustomer($workspace->customerId(), $workspace->teamId);
        $locations = FindLocation::forCustomer($workspace->customerId(), $workspace->teamId);

        return Inertia::render('customers/workspace/settings', [
            ...$workspace->props($request),
            'statuses' => CustomerStatusEnum::options(),
            'billingTypes' => CustomerBillingTypeEnum::options(),
            'currencies' => CurrencyEnum::options(),
            'contacts' => $contacts
                ->map(fn (Contact $contact): array => ['id' => $contact->getId()->toString(), 'name' => $contact->getFullName()])
                ->values()
                ->all(),
            'locations' => $locations
                ->map(fn (Location $location): array => ['id' => $location->getId()->toString(), 'name' => $location->getName()->toNullableString() ?? $location->getStreetOne()->toString()])
                ->values()
                ->all(),
            'mainContactId' => $contacts->first(fn (Contact $contact): bool => $contact->getIsPrimary()->toBoolean())->getId()->toNullableString(),
            'mainLocationId' => $locations->first(fn (Location $location): bool => $location->getIsPrimary()->toBoolean())->getId()->toNullableString(),
        ]);
    }
}
