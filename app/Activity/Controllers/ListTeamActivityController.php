<?php

declare(strict_types=1);

namespace App\Activity\Controllers;

use App\Activity\Actions\FindActivities;
use App\Activity\Activity;
use App\Activity\ActivityEloquentModel;
use App\AuditLog\Actions\FindAuditLogs;
use App\AuditLog\AuditLog;
use App\AuditLog\AuditLogEloquentModel;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class ListTeamActivityController
{
    /**
     * The current team's activity and audit trail; team owners only.
     *
     * @throws CouldNotViewTeam
     */
    public function __invoke(Request $request): Response
    {
        $team = FindTeam::byIdForMember(
            id: $request->user()?->current_team_id,
            userId: (string) $request->user()?->getAuthIdentifier(),
        );

        if ($team->isNull()) {
            throw CouldNotViewTeam::notFound();
        }

        Gate::authorize('viewActivity', $team);

        $teamId = $team->getId()->toString();

        return Inertia::render('settings/team-activity', [
            'team' => $team->toSummary(),
            ...self::lists(
                FindActivities::forTeam($teamId, pageName: 'activity_page'),
                FindAuditLogs::forTeam($teamId, pageName: 'audit_page'),
            ),
        ]);
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
