<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Ticket\Actions\FindTicket;
use App\Ticket\Exceptions\CouldNotFindTicket;
use App\Ticket\TicketFormOptions;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class EditTicketController
{
    /**
     * @throws CouldNotFindTicket
     */
    public function __invoke(Request $request, string $ticket): Response
    {
        $teamId = (string) $request->user()?->current_team_id;
        $found = FindTicket::byIdForTeam($ticket, $teamId);

        if ($found->isNull()) {
            throw CouldNotFindTicket::notFound();
        }

        return Inertia::render('tickets/edit', [
            'ticket' => $found->toSummary(),
            ...TicketFormOptions::forTeam($teamId, (string) $request->user()?->id),
        ]);
    }
}
