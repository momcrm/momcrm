<?php

declare(strict_types=1);

namespace App\Contact\Controllers;

use App\Contact\Actions\FindContact;
use App\Contact\Exceptions\CouldNotFindContact;
use App\Customer\Actions\FindCustomer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class EditContactController
{
    /**
     * @throws CouldNotFindContact
     */
    public function __invoke(Request $request, string $contact): Response
    {
        $teamId = (string) $request->user()?->current_team_id;
        $found = FindContact::byIdForTeam($contact, $teamId);

        if ($found->isNull()) {
            throw CouldNotFindContact::notFound();
        }

        return Inertia::render('contacts/edit', [
            'contact' => $found->toSummary(),
            'customers' => FindCustomer::optionsForTeam($teamId),
        ]);
    }
}
