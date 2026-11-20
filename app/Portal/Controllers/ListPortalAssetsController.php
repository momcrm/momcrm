<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\Asset\Actions\FindAsset;
use App\Asset\Asset;
use App\Asset\AssetStatusEnum;
use App\Location\LocationEloquentModel;
use App\Portal\PortalAccess;
use App\Portal\PortalContact;
use App\Shared\Types;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's equipment the team looks after: what it is, its serial
 * number and where it is. Staff descriptions and secrets are left out.
 */
final class ListPortalAssetsController
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(): Response
    {
        PortalAccess::authorizeSection(AccessResourceEnum::Asset);

        $contact = PortalContact::current();
        $assets = PortalAccess::visible(AccessResourceEnum::Asset, FindAsset::forCustomer($contact->getCustomerId()->toString(), $contact->getTeamId()->toString()));
        $locationNames = LocationEloquentModel::query()
            ->withoutGlobalScopes()
            ->whereKey($assets->map(fn (Asset $asset): ?string => $asset->getLocationId()->toNullableString())->filter()->unique()->values()->all())
            ->pluck('name', 'id');

        return Inertia::render('portal/assets', [
            'assets' => $assets->map(fn (Asset $asset): array => [
                'id' => $asset->getId()->toString(),
                'name' => $asset->getName()->toNullableString(),
                'serial_number' => $asset->getSerialNumber()->toNullableString(),
                'status' => (AssetStatusEnum::tryFrom($asset->getStatus()->toString()) ?? AssetStatusEnum::Active)->label(),
                'is_active' => $asset->getStatus()->toString() === AssetStatusEnum::Active->value,
                'location_name' => Types::nullableString($locationNames->get($asset->getLocationId()->toString())),
            ])->values()->all(),
        ]);
    }
}
