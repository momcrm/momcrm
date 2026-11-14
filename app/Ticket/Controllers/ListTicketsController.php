<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Customer\CustomerEloquentModel;
use App\Project\ProjectEloquentModel;
use App\Ticket\Actions\FindTicket;
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketRelationship;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ListTicketsController
{
    public function __invoke(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString() ?: null;
        $filter = in_array($request->query('status'), [FindTicket::FILTER_CLOSED, FindTicket::FILTER_ALL], true)
            ? (string) $request->query('status')
            : FindTicket::FILTER_OPEN;

        return Inertia::render('tickets/index', [
            'tickets' => FindTicket::paginateForTeam((string) $request->user()?->current_team_id, $filter, $search)
                ->through(fn (TicketEloquentModel $model): array => self::listItem($model)),
            'filters' => ['search' => $search, 'status' => $filter],
        ]);
    }

    /**
     * A ticket with the names of its customer, assignee and project, for lists.
     *
     * @return array<string, mixed>
     */
    public static function listItem(TicketEloquentModel $model): array
    {
        $customer = $model->getRelationValue(TicketRelationship::Customer->value);
        $assignee = $model->getRelationValue(TicketRelationship::Assignee->value);
        $project = $model->getRelationValue(TicketRelationship::Project->value);

        return [
            ...Ticket::fromEloquentModel($model)->toSummary(),
            'customer_name' => $customer instanceof CustomerEloquentModel ? $customer->name : null,
            'assignee_name' => $assignee instanceof UserEloquentModel ? $assignee->name : null,
            'project_name' => $project instanceof ProjectEloquentModel ? $project->name : null,
            'project_color' => $project instanceof ProjectEloquentModel ? $project->color : null,
        ];
    }
}
