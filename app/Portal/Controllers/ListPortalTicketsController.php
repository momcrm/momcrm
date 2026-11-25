<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\Portal\Actions\FindPortalTickets;
use App\Portal\PortalAccess;
use App\Portal\PortalContact;
use App\Portal\PortalTicketView;
use App\Ticket\Events\TicketChanged;
use App\Ticket\Ticket;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's open or closed tickets that the contact may see.
 */
final class ListPortalTicketsController
{
    /**
     * Each open list reloads at most this often, however many of the
     * customer's tickets change.
     */
    private const int LIVE_THROTTLE_MS = 2000;

    /**
     * @throws AuthorizationException
     */
    public function __invoke(Request $request): Response
    {
        PortalAccess::authorizeSection(AccessResourceEnum::Ticket);

        $contact = PortalContact::current();
        $teamId = $contact->getTeamId()->toString();
        $isClosed = $request->string('status')->toString() === 'closed';
        $statuses = FindPortalTickets::statuses($teamId);

        $tickets = PortalAccess::visible(
            AccessResourceEnum::Ticket,
            FindPortalTickets::forCustomer($teamId, $contact->getCustomerId()->toString(), $isClosed),
        );

        return Inertia::render('portal/tickets/index', [
            'status' => $isClosed ? 'closed' : 'open',
            'tickets' => TicketChanged::liveForPortalCustomer(
                $contact->getCustomerId()->toString(),
                fn (): array => $tickets->map(fn (Ticket $ticket): array => PortalTicketView::item($ticket, $statuses))->values()->all(),
                throttle: self::LIVE_THROTTLE_MS,
            ),
            'canCreate' => PortalAccess::allows(AccessResourceEnum::Ticket, AccessActionEnum::Create),
        ]);
    }
}
