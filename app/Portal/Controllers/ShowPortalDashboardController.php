<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\Customer\Actions\FindCustomer;
use App\Invoice\Actions\FindInvoice;
use App\Portal\Actions\FindPortalTickets;
use App\Portal\PortalAccess;
use App\Portal\PortalContact;
use App\Portal\PortalTicketView;
use App\Ticket\Ticket;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer portal's home page: open tickets, the latest of them, and
 * what is owing, as far as the contact may see each.
 */
final class ShowPortalDashboardController
{
    private const int RECENT_TICKETS = 3;

    public function __invoke(): Response
    {
        $contact = PortalContact::current();
        $teamId = $contact->getTeamId()->toString();
        $customerId = $contact->getCustomerId()->toString();
        $tickets = null;

        if (PortalAccess::maySeeSome(AccessResourceEnum::Ticket)) {
            $statuses = FindPortalTickets::statuses($teamId);
            $open = PortalAccess::visible(AccessResourceEnum::Ticket, FindPortalTickets::forCustomer($teamId, $customerId, isClosed: false));
            $tickets = [
                'open_count' => $open->count(),
                'recent' => $open->take(self::RECENT_TICKETS)->map(fn (Ticket $ticket): array => PortalTicketView::item($ticket, $statuses))->values()->all(),
                'can_create' => PortalAccess::allows(AccessResourceEnum::Ticket, AccessActionEnum::Create),
            ];
        }

        $owing = null;

        if (PortalAccess::allows(AccessResourceEnum::Statement, AccessActionEnum::View)) {
            $balance = FindInvoice::balancesForCustomers($teamId, [$customerId])[$customerId] ?? ['balance_in_cents' => 0, 'current_in_cents' => 0, 'overdue_in_cents' => 0];
            $owing = [
                ...$balance,
                'currency' => FindCustomer::byIdForTeam($customerId, $teamId)->getCurrency()->toEnum()->value,
            ];
        }

        return Inertia::render('portal/dashboard', [
            'tickets' => $tickets,
            'owing' => $owing,
        ]);
    }
}
