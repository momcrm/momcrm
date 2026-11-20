<?php

declare(strict_types=1);

namespace App\Asset\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\ArticleLink\Actions\FindArticleLink;
use App\ArticleLink\ArticleLinkTargetEnum;
use App\Asset\Actions\FindAsset;
use App\Asset\AssetStatusEnum;
use App\Asset\Exceptions\CouldNotFindAsset;
use App\Customer\Actions\FindCustomer;
use App\CustomField\Actions\FindLinkedRecords;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldRelationLabels;
use App\CustomField\CustomFieldTargetEnum;
use App\Location\Actions\FindLocation;
use App\Secret\Actions\FindSecret;
use App\Secret\Secret;
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
            'asset' => [...$found->toSummary(), 'labels' => $found->getLabels()->toDataCollection()->toTags()],
            'customer' => $customer->toSummary(),
            'locations' => FindLocation::optionsForCustomer($customer->getId()->toString(), (string) $customer->getTeamId()->toString()),
            'statuses' => AssetStatusEnum::options(),
            'customFieldLabels' => CustomFieldRelationLabels::for($found->getTeamId()->toString(), CustomFieldTargetEnum::Asset, $found->getCustomFields()->toValues()),
            'articles' => FindArticleLink::articlesFor(ArticleLinkTargetEnum::Asset, $found->getId()->toString(), $found->getTeamId()->toString(), (string) $request->user()?->id),
            'linkedRecords' => FindLinkedRecords::to($found->getTeamId()->toString(), (string) $request->user()?->id, CustomFieldRelationEnum::Asset, $found->getId()->toString()),
            'can' => AccessAbilities::for($request->user(), AccessResourceEnum::Asset, $found),
            'secrets' => FindSecret::forCustomer($customer->getId()->toString(), $customer->getTeamId()->toString(), $found->getId()->toString())
                ->map(fn (Secret $secret): array => $secret->toSummary())
                ->values()
                ->all(),
        ]);
    }
}
