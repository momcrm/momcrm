<?php

declare(strict_types=1);

namespace App\Service\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\Customer\Actions\FindCustomer;
use App\CustomField\Actions\FindLinkedRecords;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldRelationLabels;
use App\CustomField\CustomFieldTargetEnum;
use App\Service\Actions\FindService;
use App\Service\Exceptions\CouldNotFindService;
use App\Service\ServiceCollection;
use App\Service\ServiceFormOptions;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class EditServiceController
{
    /**
     * @throws CouldNotFindService
     */
    public function __invoke(Request $request, string $service): Response
    {
        $teamId = $request->user()?->current_team_id;
        $found = FindService::byIdForTeam($service, $teamId);

        if ($found->isNull()) {
            throw CouldNotFindService::notFound();
        }

        $customer = FindCustomer::byIdForTeam($found->getCustomerId()->toString(), $teamId);

        return Inertia::render('services/edit', [
            'service' => ServiceCollection::make([$found])->toSummaries()[0],
            'customer' => $customer->toSummary(),
            ...ServiceFormOptions::for($customer->getId()->toString(), $customer->getTeamId()->toString(), $found),
            'customFieldLabels' => CustomFieldRelationLabels::for($found->getTeamId()->toString(), CustomFieldTargetEnum::Service, $found->getCustomFields()->toValues()),
            'linkedRecords' => FindLinkedRecords::to($found->getTeamId()->toString(), (string) $request->user()?->id, CustomFieldRelationEnum::Service, $found->getId()->toString()),
            'can' => AccessAbilities::for($request->user(), AccessResourceEnum::Service, $found),
        ]);
    }
}
