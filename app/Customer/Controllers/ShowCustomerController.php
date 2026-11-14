<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Contact\Actions\FindContact;
use App\Contact\Contact;
use App\Customer\Actions\FindCustomer;
use App\Customer\Exceptions\CouldNotFindCustomer;
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

        return Inertia::render('customers/show', [
            'customer' => $found->toSummary(),
            'contacts' => FindContact::forCustomer($found->getId()->toString(), $teamId)
                ->map(fn (Contact $contact): array => $contact->toSummary())
                ->values()
                ->all(),
        ]);
    }
}
