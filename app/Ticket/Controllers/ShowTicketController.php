<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Ticket\Events\TicketChanged;
use App\Ticket\Exceptions\CouldNotFindTicket;
use App\Ticket\TicketDrawer;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A ticket's own page: the drawer's full-page view, editable in place, for
 * shared links and anywhere a ticket opens outside a list. The ticket is
 * passed as `drawer` so the drawer's edits reload it.
 */
final class ShowTicketController
{
    /**
     * @throws CouldNotFindTicket
     */
    public function __invoke(Request $request, string $ticket): Response
    {
        /** @var UserEloquentModel $user */
        $user = $request->user();
        $drawer = TicketDrawer::forTicket($ticket, $user);

        if ($drawer === null) {
            throw CouldNotFindTicket::notFound();
        }

        return Inertia::render('tickets/show', [
            'drawer' => TicketChanged::liveForTicket($ticket, fn (): array => $drawer),
            ...TicketDrawer::options((string) $user->current_team_id, $user->id),
        ]);
    }
}
