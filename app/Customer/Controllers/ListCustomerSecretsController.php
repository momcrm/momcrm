<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\Asset\Actions\FindAsset;
use App\Asset\Asset;
use App\Customer\CustomerWorkspace;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Secret\Actions\FindSecret;
use App\Secret\Secret;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's secrets tab: titles and what each is for, never the values,
 * which are fetched one secret at a time when someone reveals them.
 */
final class ListCustomerSecretsController
{
    /**
     * @throws CouldNotFindCustomer
     */
    public function __invoke(Request $request, string $customer): Response
    {
        $workspace = CustomerWorkspace::open($request, $customer);
        $assetId = Str::isUuid($request->string('asset')->toString()) ? $request->string('asset')->toString() : null;
        $search = $request->string('search')->trim()->toString() ?: null;

        return Inertia::render('customers/workspace/secrets', [
            ...$workspace->props($request),
            'secrets' => FindSecret::forCustomer($workspace->customerId(), $workspace->teamId, $assetId, $search)
                ->map(fn (Secret $secret): array => [
                    ...$secret->toSummary(),
                    'can' => AccessAbilities::for($request->user(), AccessResourceEnum::Secret, $secret),
                ])
                ->values()
                ->all(),
            'assets' => FindAsset::forCustomer($workspace->customerId(), $workspace->teamId)
                ->map(fn (Asset $asset): array => ['id' => $asset->getId()->toString(), 'name' => $asset->getName()->toString()])
                ->values()
                ->all(),
            'filters' => ['asset' => $assetId, 'search' => $search],
            'secretCan' => AccessAbilities::for($request->user(), AccessResourceEnum::Secret),
        ]);
    }
}
