<?php

declare(strict_types=1);

namespace App\Contact\Controllers;

use App\Contact\Actions\FindContact;
use App\Contact\Contact;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ListContactsController
{
    public function __invoke(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString() ?: null;

        return Inertia::render('contacts/index', [
            'contacts' => FindContact::paginateForTeam((string) $request->user()?->current_team_id, $search)
                ->through(fn (Contact $contact): array => [
                    ...$contact->toSummary(),
                    'customer_name' => $contact->getCustomerName()->toNullableString(),
                ]),
            'filters' => ['search' => $search],
        ]);
    }
}
