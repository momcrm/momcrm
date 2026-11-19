<?php

declare(strict_types=1);

namespace App\Service\Controllers;

use App\CustomField\CustomFieldRelationLabels;
use App\CustomField\CustomFieldTargetEnum;
use App\Service\Exceptions\CouldNotFindService;
use App\Service\ServiceWorkspace;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A service's overview: what it costs and how it is billed, who it is for,
 * its note and custom fields, and when it was added and changed.
 */
final class ShowServiceController
{
    /**
     * @throws CouldNotFindService
     */
    public function __invoke(Request $request, string $service): Response
    {
        $workspace = ServiceWorkspace::open($request, $service);

        return Inertia::render('services/workspace/overview', [
            ...$workspace->props($request),
            'customFieldLabels' => CustomFieldRelationLabels::for($workspace->teamId, CustomFieldTargetEnum::Service, $workspace->service->getCustomFields()->toValues()),
            'dates' => [
                'created_at' => $workspace->service->getCreatedAt()->toNullableISOString(),
                'updated_at' => $workspace->service->getUpdatedAt()->toNullableISOString(),
            ],
        ]);
    }
}
