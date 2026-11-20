<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\Contact\ContactRecipients;
use App\Customer\CustomerStatement;
use App\Customer\CustomerWorkspace;
use App\Customer\Exceptions\CouldNotFindCustomer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's statement tab: their account over a period.
 */
final class ShowCustomerStatementController
{
    /**
     * @throws CouldNotFindCustomer
     */
    public function __invoke(Request $request, string $customer): Response
    {
        $workspace = CustomerWorkspace::open($request, $customer);
        [$from, $to] = CustomerStatement::periodFrom($request);

        return Inertia::render('customers/workspace/statement', [
            ...$workspace->props($request),
            'statement' => CustomerStatement::for($workspace->customer, $from, $to)->toArray(),
            'contacts' => ContactRecipients::optionsFor($workspace->customerId(), $workspace->teamId),
            'canSend' => AccessAbilities::for($request->user(), AccessResourceEnum::Customer, $workspace->customer)['update'] ?? false,
        ]);
    }
}
