<?php

declare(strict_types=1);

namespace App\Archive\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\Favorite\Actions\FindFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Project\Actions\FindProject;
use App\Project\Controllers\ListProjectsController;
use App\Project\Project;
use App\Ticket\Actions\FindTicket;
use App\Ticket\Controllers\ListTicketsController;
use App\Ticket\Ticket;
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
                ->map(fn (Ticket $ticket): array => [
                    ...ListTicketsController::listItem($ticket),
                    'can' => AccessAbilities::for($user, AccessResourceEnum::Ticket, $ticket),
                ])
                ->values(),
            'projects' => FindProject::archivedForTeam($teamId, $user->id)
                ->map(fn (Project $project): array => [
                    ...ListProjectsController::listItem($project, $favoriteProjectIds),
                    'can' => AccessAbilities::for($user, AccessResourceEnum::Project, $project),
                ])
                ->values(),
        ]);
    }
}
