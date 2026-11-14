<?php

declare(strict_types=1);

namespace App\Team\Middleware;

use App\Team\Actions\FindTeam;
use App\Team\Actions\SwitchCurrentTeam;
use App\User\Actions\UpdateUser;
use App\User\User;
use App\User\UserEloquentModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCurrentTeam
{
    /**
     * Make sure the authenticated user's current team is one they belong to,
     * falling back to their first team when it is missing or they left it.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $model = $request->user();

        if ($model instanceof UserEloquentModel) {
            $this->repairCurrentTeam($model);
        }

        return $next($request);
    }

    private function repairCurrentTeam(UserEloquentModel $model): void
    {
        $currentTeam = FindTeam::byIdForMember(id: $model->current_team_id, userId: $model->id);

        if ($currentTeam->isNotNull()) {
            return;
        }

        $user = User::fromEloquentModel($model);
        $fallbackTeam = FindTeam::firstForMember($model->id);

        if ($fallbackTeam->isNotNull()) {
            SwitchCurrentTeam::fromTeam(user: $user, team: $fallbackTeam);
        } elseif ($model->current_team_id !== null) {
            UpdateUser::fromUser($user->setCurrentTeamId(null));
        }

        $model->refresh();
    }
}
