<?php

declare(strict_types=1);

namespace App\Activity\Controllers;

use App\Activity\Actions\FindActivities;
use App\Activity\Activity;
use App\Activity\ActivityEloquentModel;
use App\AuditLog\Actions\FindAuditLogs;
use App\AuditLog\AuditLog;
use App\AuditLog\AuditLogEloquentModel;
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
        $userId = (string) $request->user()?->getAuthIdentifier();

        return Inertia::render('settings/activity', self::lists(
            FindActivities::forUser($userId, pageName: 'activity_page'),
            FindAuditLogs::forUser($userId, pageName: 'audit_page'),
        ));
    }

    /**
     * @param  LengthAwarePaginator<int, ActivityEloquentModel>  $activities
     * @param  LengthAwarePaginator<int, AuditLogEloquentModel>  $auditLogs
     * @return array{activities: LengthAwarePaginator<int, mixed>, auditLogs: LengthAwarePaginator<int, mixed>}
     */
    private static function lists(LengthAwarePaginator $activities, LengthAwarePaginator $auditLogs): array
    {
        return [
            'activities' => $activities->through(
                fn (ActivityEloquentModel $model): array => Activity::fromEloquentModel($model)->toListItem($model->actor?->name),
            ),
            'auditLogs' => $auditLogs->through(
                fn (AuditLogEloquentModel $model): array => AuditLog::fromEloquentModel($model)->toListItem($model->actor?->name),
            ),
        ];
    }
}
