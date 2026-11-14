<?php

declare(strict_types=1);

namespace App\Archive\Controllers;

use App\Favorite\Actions\FindFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Project\Actions\FindProject;
use App\Project\Controllers\ListProjectsController;
use App\Project\ProjectEloquentModel;
use App\Ticket\Actions\FindTicket;
use App\Ticket\Controllers\ListTicketsController;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Archived tickets and projects, to restore or delete for good.
 */
final class ShowArchiveController
{
    public function __invoke(Request $request): Response
    {
        /** @var UserEloquentModel $user */
        $user = $request->user();
        $teamId = (string) $user->current_team_id;
        $favoriteProjectIds = FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Project);

        return Inertia::render('archive', [
            'tab' => $request->query('tab') === 'projects' ? 'projects' : 'tickets',
            'tickets' => FindTicket::archivedForTeam($teamId)
                ->map(fn (TicketEloquentModel $model): array => ListTicketsController::listItem($model))
                ->values(),
            'projects' => FindProject::archivedForTeam($teamId, $user->id)
                ->map(fn (ProjectEloquentModel $model): array => ListProjectsController::listItem($model, $favoriteProjectIds))
                ->values(),
        ]);
    }
}
