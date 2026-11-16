<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Ticket\TicketListFilters;
use App\Ticket\TicketViewProps;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The ticket views (list, board and table, with the same filters as the
 * tickets page) limited to the signed-in person's tickets.
 */
final class ListMyTicketsController
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('tickets/mine', TicketViewProps::for(
            $request,
            TicketListFilters::fromRequest($request)->forAssignee(TicketListFilters::ASSIGNEE_ME),
        ));
    }
}
