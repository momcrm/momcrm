<?php

declare(strict_types=1);

namespace App\Ticket;

use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Authorizes changes that can close or reopen a ticket on the way (dragging
 * it on the board, editing its status inline), so closing is governed by the
 * same rules however it happens.
 */
final class TicketAccess
{
    public static function authorizeStatusChange(string $ability, Ticket $ticket, mixed $newStatus): Response
    {
        Gate::authorize($ability, $ticket);

        $wasClosed = $ticket->getStatus()->toNullableEnum() === TicketStatusEnum::Closed;
        $becomes = is_string($newStatus) ? TicketStatusEnum::tryFrom($newStatus) : null;

        return match (true) {
            $becomes === TicketStatusEnum::Closed && ! $wasClosed => Gate::authorize('close', $ticket),
            $becomes !== null && $becomes !== TicketStatusEnum::Closed && $wasClosed => Gate::authorize('reopen', $ticket),
            default => Response::allow(),
        };
    }
}
