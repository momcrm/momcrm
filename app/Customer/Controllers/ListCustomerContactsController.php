<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Contact\Actions\FindContact;
use App\Contact\Contact;
use App\Customer\CustomerWorkspace;
use App\Customer\Exceptions\CouldNotFindCustomer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's contacts tab, main contact first.
 */
final class ListCustomerContactsController
{
    /**
     * @throws CouldNotFindCustomer
     */
    public function __invoke(Request $request, string $customer): Response
    {
        $workspace = CustomerWorkspace::open($request, $customer);

        return Inertia::render('customers/workspace/contacts', [
            ...$workspace->props($request),
            'contacts' => FindContact::forCustomer($workspace->customerId(), $workspace->teamId)
                ->map(fn (Contact $contact): array => $contact->toSummary())
                ->values()
                ->all(),
        ]);
    }
}
