<?php

declare(strict_types=1);

namespace App\Service\Controllers;

use App\Service\Exceptions\CouldNotFindService;
use App\Service\ServiceWorkspace;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The tickets, assets, contracts and other records that point at a service
 * through record-link custom fields.
 */
final class ListServiceLinkedRecordsController
{
    /**
     * @throws CouldNotFindService
     */
    public function __invoke(Request $request, string $service): Response
    {
        $workspace = ServiceWorkspace::open($request, $service);

        return Inertia::render('services/workspace/linked', [
            ...$workspace->props($request),
            'linkedRecords' => $workspace->linkedRecords(),
        ]);
    }
}
