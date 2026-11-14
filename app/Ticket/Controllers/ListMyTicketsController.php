<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Favorite\Actions\FindFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Ticket\Actions\FindTicket;
use App\Ticket\TicketDrawer;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The signed-in person's open tickets, and those closed in the last two
 * weeks. The page groups them into overdue, today, upcoming and done.
 */
final class ListMyTicketsController
{
    public function __invoke(Request $request): Response
    {
        /** @var UserEloquentModel $user */
        $user = $request->user();
        $teamId = (string) $user->current_team_id;

        return Inertia::render('tickets/mine', [
            'tickets' => FindTicket::assignedTo($teamId, $user->id)
                ->map(fn (TicketEloquentModel $model): array => ListTicketsController::listItem($model))
                ->values(),
            'favoriteTicketIds' => FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Ticket),
            ...TicketDrawer::options($teamId),
            'drawer' => TicketDrawer::forRequest($request),
        ]);
    }
}
