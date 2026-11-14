<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Ticket\TicketFormOptions;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CreateTicketController
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('tickets/create', [
            ...TicketFormOptions::forTeam((string) $request->user()?->current_team_id),
            'customerId' => $request->string('customer')->toString() ?: null,
        ]);
    }
}
