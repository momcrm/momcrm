<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Favorite\Actions\FindFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Ticket\TicketDrawer;
use App\Ticket\TicketTimeline;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The workspace timeline. Tickets open in the drawer and are rescheduled by dragging.
 */
final class ShowTimelineController
{
    public function __invoke(Request $request): Response
    {
        /** @var UserEloquentModel $user */
        $user = $request->user();
        $teamId = (string) $user->current_team_id;

        return Inertia::render('timeline', [
            ...TicketTimeline::forRequest($request),
            'favoriteTicketIds' => FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Ticket),
            ...TicketDrawer::options($teamId),
            'drawer' => TicketDrawer::forRequest($request),
        ]);
    }
}
