<?php

declare(strict_types=1);

namespace App\Activity\Controllers;

use App\Activity\Actions\FindActivities;
use App\Activity\Activity;
use App\AuditLog\Actions\FindAuditLogs;
use App\AuditLog\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

final class ListAccountActivityController
{
    /**
     * The signed-in user's own account activity and account-level audit trail.
     */
    public function __invoke(Request $request): Response
    {
        $userId = (string) $request->user()?->id;

        return Inertia::render('settings/activity', self::lists(
            FindActivities::forUser($userId, pageName: 'activity_page'),
            FindAuditLogs::forUser($userId, pageName: 'audit_page'),
        ));
    }

    /**
     * @param  LengthAwarePaginator<int, Activity>  $activities
     * @param  LengthAwarePaginator<int, AuditLog>  $auditLogs
     * @return array{activities: LengthAwarePaginator<int, covariant array<string, mixed>>, auditLogs: LengthAwarePaginator<int, covariant array<string, mixed>>}
     */
    private static function lists(LengthAwarePaginator $activities, LengthAwarePaginator $auditLogs): array
    {
        return [
            'activities' => $activities->through(
                fn (Activity $activity): array => $activity->toListItem(),
            ),
            'auditLogs' => $auditLogs->through(
                fn (AuditLog $auditLog): array => $auditLog->toListItem(),
            ),
        ];
    }
}
