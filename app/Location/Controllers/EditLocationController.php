<?php

declare(strict_types=1);

namespace App\Location\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\Customer\Actions\FindCustomer;
use App\Location\Actions\FindLocation;
use App\Location\Exceptions\CouldNotFindLocation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class EditLocationController
{
    /**
     * @throws CouldNotFindLocation
     */
    public function __invoke(Request $request, string $location): Response
    {
        $teamId = $request->user()?->current_team_id;
        $found = FindLocation::byIdForTeam($location, $teamId);

        if ($found->isNull()) {
            throw CouldNotFindLocation::notFound();
        }

        $customer = FindCustomer::byIdForTeam($found->getCustomerId()->toString(), $teamId);

        return Inertia::render('locations/edit', [
            'location' => $found->toSummary(),
            'customer' => $customer->toSummary(),
            'can' => AccessAbilities::for($request->user(), AccessResourceEnum::Location, $found),
        ]);
    }
}
