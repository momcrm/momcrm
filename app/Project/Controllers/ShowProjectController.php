<?php

declare(strict_types=1);

namespace App\Project\Controllers;

use App\Favorite\Actions\FindFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Group\GroupEloquentModel;
use App\Project\Actions\FindProject;
use App\Project\Exceptions\CouldNotFindProject;
use App\Project\ProjectFormOptions;
use App\Project\ProjectOverview;
use App\Project\ProjectRelationship;
use App\Ticket\TicketDrawer;
use App\Ticket\TicketListFilters;
use App\Ticket\TicketViewProps;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A project's page. The Overview tab sums it up; the List, Board and Table
 * tabs are the ticket views limited to the project.
 */
final class ShowProjectController
{
    public const array TABS = ['overview', TicketListFilters::VIEW_LIST, TicketListFilters::VIEW_BOARD, TicketListFilters::VIEW_TABLE];

    /**
     * @throws CouldNotFindProject
     */
    public function __invoke(Request $request, string $project): Response
    {
        /** @var UserEloquentModel $user */
        $user = $request->user();
        $teamId = (string) $user->current_team_id;
        $model = FindProject::withDetailsForTeam($project, $teamId, $user->id);

        if ($model === null) {
            throw CouldNotFindProject::notFound();
        }

        $tab = in_array($request->query('view'), self::TABS, true) ? (string) $request->query('view') : 'overview';
        $group = $model->getRelationValue(ProjectRelationship::Group->value);

        return Inertia::render('projects/show', [
            'project' => [
                ...ListProjectsController::listItem($model, FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Project)),
                'group_name' => $group instanceof GroupEloquentModel ? $group->name : null,
            ],
            'tab' => $tab,
            ...($tab === 'overview'
                ? [
                    ...ProjectOverview::for($model->id),
                    'favoriteTicketIds' => FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Ticket),
                    ...TicketDrawer::options($teamId),
                    'drawer' => TicketDrawer::forRequest($request),
                ]
                : TicketViewProps::for($request, TicketListFilters::fromRequest($request)->forProject($model->id))),
            ...ProjectFormOptions::forTeam($teamId),
        ]);
    }
}
