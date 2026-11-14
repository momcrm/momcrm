<?php

declare(strict_types=1);

namespace App\Ticket\Scopes;

use App\Project\ProjectVisibility;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Hides tickets in members-only projects from people who are neither members
 * of the project nor the ticket's assignee. Without an authenticated user
 * (console, queue) nothing is hidden.
 *
 * @template TModel of Model
 *
 * @implements Scope<TModel>
 */
final class VisibleTicketScope implements Scope
{
    /**
     * @param  Builder<covariant TModel>  $builder
     * @param  TModel  $model
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if ($user instanceof UserEloquentModel) {
            ProjectVisibility::constrainTickets($builder->getQuery(), $user->id, $model->getTable());
        }
    }
}
