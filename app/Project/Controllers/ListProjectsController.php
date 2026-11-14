<?php

declare(strict_types=1);

namespace App\Project\Controllers;

use App\Customer\CustomerEloquentModel;
use App\Favorite\Actions\FindFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Project\Actions\FindProject;
use App\Project\Project;
use App\Project\ProjectEloquentModel;
use App\Project\ProjectFormOptions;
use App\Project\ProjectRelationship;
use App\Project\ProjectStatusEnum;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The team's projects as cards, a list or a table, with search, a status
 * filter and a sort.
 */
final class ListProjectsController
{
    public function __invoke(Request $request): Response
    {
        /** @var UserEloquentModel $user */
        $user = $request->user();
        $teamId = (string) $user->current_team_id;
        $search = $request->string('search')->trim()->toString() ?: null;
        $status = ProjectStatusEnum::tryFrom($request->string('status')->toString())?->value;
        $sort = in_array($request->query('sort'), FindProject::SORTS, true) ? (string) $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $view = in_array($request->query('view'), ['list', 'table'], true) ? (string) $request->query('view') : 'grid';
        $favoriteIds = FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Project);

        return Inertia::render('projects/index', [
            'projects' => FindProject::listForTeam($teamId, $user->id, $search, $status, $sort, $direction)
                ->map(fn (ProjectEloquentModel $model): array => self::listItem($model, $favoriteIds))
                ->values(),
            'filters' => ['search' => $search, 'status' => $status, 'sort' => $sort, 'direction' => $direction, 'view' => $view],
            ...ProjectFormOptions::forTeam($teamId),
        ]);
    }

    /**
     * A project with its customer, lead, members and ticket progress, for
     * cards, rows and the project page header.
     *
     * @param  array<int, string>  $favoriteIds
     * @return array<string, mixed>
     */
    public static function listItem(ProjectEloquentModel $model, array $favoriteIds): array
    {
        $customer = $model->getRelationValue(ProjectRelationship::Customer->value);
        $lead = $model->getRelationValue(ProjectRelationship::Lead->value);

        return [
            ...Project::fromEloquentModel($model)->toSummary(),
            'customer_name' => $customer instanceof CustomerEloquentModel ? $customer->name : null,
            'lead_name' => $lead instanceof UserEloquentModel ? $lead->name : null,
            'members' => $model->members
                ->map(fn (UserEloquentModel $member): array => ['id' => $member->id, 'name' => $member->name])
                ->values()
                ->all(),
            'tickets_count' => (int) $model->getAttribute('tickets_count'),
            'closed_tickets_count' => (int) $model->getAttribute('closed_tickets_count'),
            'overdue_tickets_count' => (int) $model->getAttribute('overdue_tickets_count'),
            'is_favorite' => in_array($model->id, $favoriteIds, true),
            'updated_at' => $model->updated_at?->toIso8601String(),
        ];
    }
}
