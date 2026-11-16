<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Customer\CustomerWorkspace;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Ticket\Actions\FindTicket;
use App\Ticket\Controllers\ListTicketsController;
use App\Ticket\Ticket;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's tickets tab: open, closed or all of them, searchable.
 */
final class ListCustomerTicketsController
{
    /**
     * @throws CouldNotFindCustomer
     */
    public function __invoke(Request $request, string $customer): Response
    {
        $workspace = CustomerWorkspace::open($request, $customer);
        $show = in_array($request->string('show')->toString(), [FindTicket::FILTER_CLOSED, FindTicket::FILTER_ALL], true)
            ? $request->string('show')->toString()
            : FindTicket::FILTER_OPEN;
        $search = $request->string('search')->trim()->toString() ?: null;

        return Inertia::render('customers/workspace/tickets', [
            ...$workspace->props($request),
            'tickets' => FindTicket::paginateForTeam($workspace->teamId, $show, $search, $workspace->customerId())
                ->through(fn (Ticket $ticket): array => ListTicketsController::listItem($ticket)),
            'filters' => ['show' => $show, 'search' => $search],
        ]);
    }
}
