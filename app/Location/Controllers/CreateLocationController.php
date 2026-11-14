<?php

declare(strict_types=1);

namespace App\Location\Controllers;

use App\Customer\Actions\FindCustomer;
use App\Customer\Exceptions\CouldNotFindCustomer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CreateLocationController
{
    /**
     * @throws CouldNotFindCustomer
     */
    public function __invoke(Request $request, string $customer): Response
    {
        $found = FindCustomer::byIdForTeam($customer, $request->user()?->current_team_id);

        if ($found->isNull()) {
            throw CouldNotFindCustomer::notFound();
        }

        return Inertia::render('locations/create', [
            'customer' => $found->toSummary(),
        ]);
    }
}
