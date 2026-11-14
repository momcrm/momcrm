<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Customer\CustomerEloquentModel;
use App\Favorite\Actions\FindFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Label\LabelEloquentModel;
use App\Project\ProjectEloquentModel;
use App\Ticket\Actions\FindTicket;
use App\Ticket\Ticket;
use App\Ticket\TicketDrawer;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketListFilters;
use App\Ticket\TicketRelationship;
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
    private const int TABLE_PAGE_SIZE = 25;

    private const int BOARD_LIMIT = 500;

    public function __invoke(Request $request): Response
    {
        /** @var UserEloquentModel $user */
        $user = $request->user();
        $teamId = (string) $user->current_team_id;
        $filters = TicketListFilters::fromRequest($request);
        $perPage = $filters->view === TicketListFilters::VIEW_TABLE ? self::TABLE_PAGE_SIZE : self::BOARD_LIMIT;

        return Inertia::render('tickets/index', [
            'tickets' => FindTicket::paginateFiltered($teamId, $user->id, $filters, $perPage)
                ->through(fn (TicketEloquentModel $model): array => self::listItem($model)),
            'filters' => $filters->toArray(),
            'favoriteTicketIds' => FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Ticket),
            ...TicketDrawer::options($teamId),
            'drawer' => TicketDrawer::forRequest($request),
        ]);
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
