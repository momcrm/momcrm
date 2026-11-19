<?php

declare(strict_types=1);

namespace App\Service\Controllers;

use App\Activity\Actions\FindActivities;
use App\Activity\Activity;
use App\AuditLog\Actions\FindAuditLogs;
use App\AuditLog\AuditLog;
use App\Service\Exceptions\CouldNotFindService;
use App\Service\ServiceWorkspace;
use App\Shared\MorphMapKeyEnum;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * What has happened to a service: its lifecycle and rate changes, and every
 * change to its fields.
 */
final class ListServiceActivityController
{
    /**
     * @throws CouldNotFindService
     */
    public function __invoke(Request $request, string $service): Response
    {
        $workspace = ServiceWorkspace::open($request, $service);

        return Inertia::render('services/workspace/activity', [
            ...$workspace->props($request),
            'activities' => FindActivities::forSubject($workspace->teamId, MorphMapKeyEnum::Service, $workspace->serviceId(), pageName: 'activity_page')
                ->through(fn (Activity $activity): array => $activity->toListItem()),
            'auditLogs' => FindAuditLogs::forAuditable($workspace->teamId, MorphMapKeyEnum::Service, $workspace->serviceId(), pageName: 'audit_page')
                ->through(fn (AuditLog $auditLog): array => $auditLog->toListItem()),
        ]);
    }
}
