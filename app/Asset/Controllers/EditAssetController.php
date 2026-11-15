<?php

declare(strict_types=1);

namespace App\Asset\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\Asset\Actions\FindAsset;
use App\Asset\AssetStatusEnum;
use App\Asset\Exceptions\CouldNotFindAsset;
use App\Customer\Actions\FindCustomer;
use App\Location\Actions\FindLocation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class EditAssetController
{
    /**
     * @throws CouldNotFindAsset
     */
    public function __invoke(Request $request, string $asset): Response
    {
        $teamId = $request->user()?->current_team_id;
        $found = FindAsset::byIdForTeam($asset, $teamId);

        if ($found->isNull()) {
            throw CouldNotFindAsset::notFound();
        }

        $customer = FindCustomer::byIdForTeam($found->getCustomerId()->toString(), $teamId);

        return Inertia::render('assets/edit', [
            'asset' => $found->toSummary(),
            'customer' => $customer->toSummary(),
            'locations' => FindLocation::optionsForCustomer($customer->getId()->toString(), (string) $customer->getTeamId()->toString()),
            'statuses' => AssetStatusEnum::options(),
            'can' => AccessAbilities::for($request->user(), AccessResourceEnum::Asset, $found),
        ]);
    }
}
