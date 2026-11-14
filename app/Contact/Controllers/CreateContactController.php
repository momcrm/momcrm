<?php

declare(strict_types=1);

namespace App\Contact\Controllers;

use App\Customer\Actions\FindCustomer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CreateContactController
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('contacts/create', [
            'customers' => FindCustomer::optionsForTeam((string) $request->user()?->current_team_id),
            'customerId' => $request->string('customer')->toString() ?: null,
        ]);
    }
}
