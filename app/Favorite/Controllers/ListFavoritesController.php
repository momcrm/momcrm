<?php

declare(strict_types=1);

namespace App\Favorite\Controllers;

use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\Favorite\Actions\FindFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Project\Actions\FindProject;
use App\Project\Controllers\ListProjectsController;
use App\Project\ProjectEloquentModel;
use App\Ticket\Actions\FindTicket;
use App\Ticket\Controllers\ListTicketsController;
use App\Ticket\TicketDrawer;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Everything the signed-in person has starred: projects as cards, then
 * tickets and customers.
 */
final class ListFavoritesController
{
    public function __invoke(Request $request): Response
    {
        /** @var UserEloquentModel $user */
        $user = $request->user();
        $teamId = (string) $user->current_team_id;
        $projectIds = FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Project);
        $ticketIds = FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Ticket);

        return Inertia::render('favorites', [
            'projects' => FindProject::liveByIds($teamId, $user->id, $projectIds)
                ->map(fn (ProjectEloquentModel $model): array => ListProjectsController::listItem($model, $projectIds))
                ->values(),
            'tickets' => FindTicket::byIdsForTeam($teamId, $ticketIds)
                ->map(fn (TicketEloquentModel $model): array => ListTicketsController::listItem($model))
                ->values(),
            'customers' => FindCustomer::byIdsForTeam($teamId, FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Customer))
                ->map(fn (Customer $customer): array => $customer->toSummary())
                ->values(),
            'favoriteTicketIds' => $ticketIds,
            ...TicketDrawer::options($teamId, $user->id),
            'drawer' => TicketDrawer::forRequest($request),
        ]);
    }
}
