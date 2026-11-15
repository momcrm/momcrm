<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Ticket\Ticket;
use App\Ticket\TicketListFilters;
use App\Ticket\TicketViewProps;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The ticket views: a list grouped by status, a board with a column per status
 * and a sortable table, all sharing one set of filters. `?ticket=` opens a
 * ticket in the side drawer.
 */
final class ListTicketsController
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('tickets/index', TicketViewProps::for($request, TicketListFilters::fromRequest($request)));
    }

    /**
     * A ticket with the names of its customer, assignee and project, its labels
     * and subtask progress, for lists.
     *
     * @return array<string, mixed>
     */
    public static function listItem(Ticket $ticket): array
    {
        return [
            ...$ticket->toSummary(),
            'customer_name' => $ticket->getCustomer()->toNullableData()?->getName()->toString(),
            'assignee_name' => $ticket->getAssignee()->toNullableData()?->getName()->toString(),
            'project_name' => $ticket->getProject()->toNullableData()?->getName()->toString(),
            'project_hue' => $ticket->getProject()->toNullableData()?->getHue()->toNullableInteger(),
            'labels' => $ticket->getLabels()->toDataCollection()->toTags(),
            'subtasks_count' => $ticket->getSubtasksCount()->toInteger(),
            'closed_subtasks_count' => $ticket->getClosedSubtasksCount()->toInteger(),
        ];
    }
}
