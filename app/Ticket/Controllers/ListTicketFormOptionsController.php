<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Ticket\TicketFormOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The pickers' choices for the new-ticket dialog, fetched when it opens.
 */
final class ListTicketFormOptionsController
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json(
            TicketFormOptions::forTeam((string) $request->user()?->current_team_id, (string) $request->user()?->id),
        );
    }
}
