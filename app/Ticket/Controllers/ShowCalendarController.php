<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Attendance\LeaveCalendarLayer;
use App\Favorite\Actions\FindFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Team\Actions\FindTeam;
use App\Ticket\TicketCalendar;
use App\Ticket\TicketDrawer;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The workspace calendar. Tickets open in the drawer and are rescheduled by
 * dragging. Approved leave shows alongside them, so it's clear who is away.
 */
final class ShowCalendarController
{
    public function __invoke(Request $request): Response
    {
        /** @var UserEloquentModel $user */
        $user = $request->user();
        $teamId = (string) $user->current_team_id;

        $calendar = TicketCalendar::forRequest($request);
        $team = FindTeam::byIdForMember($teamId, $user->id);

        return Inertia::render('calendar', [
            ...$calendar,
            'leave' => LeaveCalendarLayer::forTeam($teamId, $calendar['calendar']['from'], $calendar['calendar']['to'], $user->id, $team->isNotNull() && Gate::allows('manageAttendance', $team)),
            'favoriteTicketIds' => FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Ticket),
            ...TicketDrawer::options($teamId, $user->id),
            'drawer' => TicketDrawer::forRequest($request),
        ]);
    }
}
