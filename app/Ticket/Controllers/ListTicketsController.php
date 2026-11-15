<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Shared\Types;
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
            'customer_name' => $ticket->getCustomerName()->toNullableString(),
            'assignee_name' => $ticket->getAssigneeName()->toNullableString(),
            'project_name' => $ticket->getProjectName()->toNullableString(),
            'project_color' => $ticket->getProjectColor()->toNullableString(),
            'labels' => $ticket->getLabels()->toCollection()
                ->map(function (mixed $label): array {
                    $label = Types::stringKeyed($label);

                    return ['id' => Types::string($label['id'] ?? null), 'name' => Types::string($label['name'] ?? null), 'color' => Types::string($label['color'] ?? null)];
                })
                ->values()
                ->all(),
            'subtasks_count' => $ticket->getSubtasksCount()->toInteger(),
            'closed_subtasks_count' => $ticket->getClosedSubtasksCount()->toInteger(),
        ];
    }
}
