<?php

declare(strict_types=1);

namespace App\Team\Scopes;

use App\Portal\PortalAuth;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Limits a team-owned model to the authenticated user's current team, or in
 * the customer portal to the signed-in contact's team.
 *
 * Apply with #[ScopedBy(CurrentTeamScope::class)] on an EloquentModel that has
 * a team_id column. Create actions set team_id explicitly from the DTO. Without
 * an authenticated user (console, queue) no constraint is added, so that code
 * must scope its own queries.
 *
 * @template TModel of Model
 *
 * @implements Scope<TModel>
 */
final class CurrentTeamScope implements Scope
{
    public const string TEAM_COLUMN = 'team_id';

    /**
     * @param  Builder<covariant TModel>  $builder
     * @param  TModel  $model
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (PortalAuth::isServingPortal()) {
            $builder->where($model->qualifyColumn(self::TEAM_COLUMN), PortalAuth::contact()->team_id ?? null);

            return;
        }

        $user = Auth::user();

        if ($user instanceof UserEloquentModel) {
            $builder->where($model->qualifyColumn(self::TEAM_COLUMN), $user->current_team_id);
        }
    }
}
