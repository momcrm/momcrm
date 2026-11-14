<?php

declare(strict_types=1);

namespace App\Asset\Controllers;

use App\Asset\AssetStatusEnum;
use App\Customer\Actions\FindCustomer;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Location\Actions\FindLocation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CreateAssetController
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

        return Inertia::render('assets/create', [
            'customer' => $found->toSummary(),
            'locations' => FindLocation::optionsForCustomer($found->getId()->toString(), $found->getTeamId()->toString()),
            'statuses' => AssetStatusEnum::options(),
        ]);
    }
}
