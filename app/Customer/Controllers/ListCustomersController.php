<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ListCustomersController
{
    public function __invoke(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString() ?: null;

        return Inertia::render('customers/index', [
            'customers' => FindCustomer::paginateForTeam((string) $request->user()?->current_team_id, $search)
                ->through(fn (Customer $customer): array => [
                    ...$customer->toSummary(),
                    'contacts_count' => $customer->getContactsCount()->toInteger(),
                ]),
            'filters' => ['search' => $search],
        ]);
    }
}
