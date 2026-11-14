<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Favorite\Actions\FindFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Ticket\TicketCalendar;
use App\Ticket\TicketDrawer;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The workspace calendar. Tickets open in the drawer and are rescheduled by dragging.
 */
final class ShowCalendarController
{
    public function __invoke(Request $request): Response
    {
        /** @var UserEloquentModel $user */
        $user = $request->user();
        $teamId = (string) $user->current_team_id;

        return Inertia::render('calendar', [
            ...TicketCalendar::forRequest($request),
            'favoriteTicketIds' => FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Ticket),
            ...TicketDrawer::options($teamId),
            'drawer' => TicketDrawer::forRequest($request),
        ]);
    }
}
