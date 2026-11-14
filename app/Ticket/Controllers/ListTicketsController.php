<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Customer\CustomerEloquentModel;
use App\Label\LabelEloquentModel;
use App\Project\ProjectEloquentModel;
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketListFilters;
use App\Ticket\TicketRelationship;
use App\Ticket\TicketViewProps;
use App\User\UserEloquentModel;
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
            'labels' => $model->relationLoaded(TicketRelationship::Labels->value)
                ? $model->labels->map(fn (LabelEloquentModel $label): array => ['id' => $label->id, 'name' => $label->name, 'color' => $label->color])->values()->all()
                : [],
            'subtasks_count' => (int) $model->getAttribute('subtasks_count'),
            'closed_subtasks_count' => (int) $model->getAttribute('closed_subtasks_count'),
        ];
    }
}
