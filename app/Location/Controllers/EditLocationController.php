<?php

declare(strict_types=1);

namespace App\Location\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\Customer\Actions\FindCustomer;
use App\CustomField\Actions\FindLinkedRecords;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldRelationLabels;
use App\CustomField\CustomFieldTargetEnum;
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
            'customFieldLabels' => CustomFieldRelationLabels::for($found->getTeamId()->toString(), CustomFieldTargetEnum::Location, $found->getCustomFields()->toValues()),
            'linkedRecords' => FindLinkedRecords::to($found->getTeamId()->toString(), (string) $request->user()?->id, CustomFieldRelationEnum::Location, $found->getId()->toString()),
            'can' => AccessAbilities::for($request->user(), AccessResourceEnum::Location, $found),
        ]);
    }
}
