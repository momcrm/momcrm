<?php

declare(strict_types=1);

namespace App\Service\Controllers;

use App\CustomField\CustomFieldRelationLabels;
use App\CustomField\CustomFieldTargetEnum;
use App\Service\Exceptions\CouldNotFindService;
use App\Service\ServiceFormOptions;
use App\Service\ServiceWorkspace;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A service's settings: its details, its rate, turning it on or off, and
 * archiving or deleting it.
 */
final class EditServiceController
{
    /**
     * @throws CouldNotFindService
     */
    public function __invoke(Request $request, string $service): Response
    {
        $workspace = ServiceWorkspace::open($request, $service);

        return Inertia::render('services/workspace/settings', [
            ...$workspace->props($request),
            ...ServiceFormOptions::for($workspace->customer->getId()->toString(), $workspace->teamId, $workspace->service),
            'customFieldLabels' => CustomFieldRelationLabels::for($workspace->teamId, CustomFieldTargetEnum::Service, $workspace->service->getCustomFields()->toValues()),
        ]);
    }
}
