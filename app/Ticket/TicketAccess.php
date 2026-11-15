<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Choice\Actions\FindChoice;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Authorizes changes that can close or reopen a ticket on the way (dragging
 * it on the board, editing its status inline), so closing is governed by the
 * same rules however it happens.
 */
final class TicketAccess
{
    public static function authorizeStatusChange(string $ability, Ticket $ticket, mixed $newStatusId): Response
    {
        Gate::authorize($ability, $ticket);

        $teamId = $ticket->getTeamId()->toString();
        $wasClosed = FindChoice::byIdForTeam($ticket->getStatusId()->toNullableString(), $teamId)->hasCategory(TicketStatusCategoryEnum::Closed);
        $becomes = FindChoice::byIdForTeam(is_string($newStatusId) ? $newStatusId : null, $teamId);
        $becomesClosed = $becomes->hasCategory(TicketStatusCategoryEnum::Closed);

        return match (true) {
            $becomes->isNull() => Response::allow(),
            $becomesClosed && ! $wasClosed => Gate::authorize('close', $ticket),
            ! $becomesClosed && $wasClosed => Gate::authorize('reopen', $ticket),
            default => Response::allow(),
        };
    }
}
