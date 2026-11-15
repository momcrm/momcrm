<?php

declare(strict_types=1);

use App\Choice\Actions\FindChoice;
use App\Choice\ChoiceListEnum;
use App\Customer\CustomerEloquentModel;
use App\Label\LabelEloquentModel;
use App\Project\ProjectEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketStatusEnum;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

function ticketFor(UserEloquentModel $user, array $attributes = []): TicketEloquentModel
{
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT']);

    return TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, ...$attributes]);
}

it('lists open tickets newest first by default', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    ticketFor($user, ['number' => 1, 'title' => 'Older']);
    ticketFor($user, ['number' => 2, 'title' => 'Newer', 'status' => TicketStatusEnum::InProgress->value]);
    ticketFor($user, ['number' => 3, 'title' => 'Done', 'status' => TicketStatusEnum::Closed->value]);
    TicketEloquentModel::factory()->create(['title' => 'Another team']);

    actingAs($user)
        ->get(route('tickets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tickets/index')
            ->where('filters.status', 'open')
            ->where('tickets.total', 2)
            ->where('tickets.data.0.title', 'Newer')
            ->where('tickets.data.0.customer_name', 'Acme IT')
            ->where('tickets.data.1.title', 'Older'),
        );
});

it('filters closed and all tickets', function (string $status, int $count): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    ticketFor($user, ['number' => 1]);
    ticketFor($user, ['number' => 2, 'status' => TicketStatusEnum::Closed->value]);

    actingAs($user)
        ->get(route('tickets.index', ['status' => $status]))
        ->assertInertia(fn (Assert $page) => $page->where('tickets.total', $count)->where('filters.status', $status));
})->with([
    'closed' => ['closed', 1],
    'all' => ['all', 2],
]);

it('finds a ticket by its number or title', function (string $search): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    ticketFor($user, ['number' => 41, 'title' => 'Printer offline']);
    ticketFor($user, ['number' => 42, 'title' => 'New laptop']);

    actingAs($user)
        ->get(route('tickets.index', ['search' => $search]))
        ->assertInertia(fn (Assert $page) => $page->where('tickets.total', 1)->where('tickets.data.0.number', 41));
})->with(['#41', 'printer']);

it('shows every status on the board, each column in its saved order', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    ticketFor($user, ['number' => 1, 'title' => 'Second', 'position' => 1]);
    ticketFor($user, ['number' => 2, 'title' => 'First', 'position' => 0]);
    ticketFor($user, ['number' => 3, 'title' => 'Done', 'status' => TicketStatusEnum::Closed->value, 'position' => 2]);

    actingAs($user)
        ->get(route('tickets.index', ['view' => 'board']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.view', 'board')
            ->where('filters.status', 'all')
            ->where('tickets.total', 3)
            ->where('tickets.data.0.title', 'First')
            ->where('tickets.data.1.title', 'Second'),
        );
});

it('filters by priority, assignee, project and label', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $label = LabelEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    [$urgent, $high, $low] = array_map(fn (string $name): string => FindChoice::byNameForTeam($user->current_team_id, ChoiceListEnum::TicketPriority, $name)->getId()->toString(), ['Urgent', 'High', 'Low']);
    $match = ticketFor($user, ['number' => 1, 'priority_id' => $urgent, 'assignee_id' => $user->id, 'project_id' => $project->id]);
    $match->labels()->attach($label);
    ticketFor($user, ['number' => 2, 'priority_id' => $urgent, 'assignee_id' => $user->id]);
    ticketFor($user, ['number' => 3, 'priority_id' => $low, 'project_id' => $project->id]);

    actingAs($user)
        ->get(route('tickets.index', ['priority' => "{$urgent},{$high}", 'assignee' => 'me', 'project' => $project->id, 'label' => $label->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('tickets.total', 1)
            ->where('tickets.data.0.id', $match->id)
            ->where('tickets.data.0.labels.0.name', $label->name)
            ->where('filters.priority', [$urgent, $high]),
        );

    actingAs($user)
        ->get(route('tickets.index', ['assignee' => 'none', 'project' => 'none']))
        ->assertInertia(fn (Assert $page) => $page->where('tickets.total', 0));
});

it("sorts the table by priority, in the team's order", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    [$urgent, $normal, $low] = array_map(fn (string $name): string => FindChoice::byNameForTeam($user->current_team_id, ChoiceListEnum::TicketPriority, $name)->getId()->toString(), ['Urgent', 'Normal', 'Low']);
    ticketFor($user, ['number' => 1, 'priority_id' => $low]);
    ticketFor($user, ['number' => 2, 'priority_id' => $urgent]);
    ticketFor($user, ['number' => 3, 'priority_id' => $normal]);
    $sorted = fn (string $direction): array => collect(actingAs($user)
        ->get(route('tickets.index', ['view' => 'table', 'sort' => 'priority', 'direction' => $direction]))
        ->inertiaProps('tickets.data'))
        ->pluck('priority_id')
        ->all();

    expect($sorted('desc'))->toBe([$urgent, $normal, $low])
        ->and($sorted('asc'))->toBe([$low, $normal, $urgent]);
});

it('leaves subtasks out of the views and counts them on their parent', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $parent = ticketFor($user, ['number' => 1]);
    ticketFor($user, ['number' => 2, 'parent_id' => $parent->id, 'status' => 'closed']);
    ticketFor($user, ['number' => 3, 'parent_id' => $parent->id]);

    actingAs($user)
        ->get(route('tickets.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('tickets.total', 1)
            ->where('tickets.data.0.subtasks_count', 2)
            ->where('tickets.data.0.closed_subtasks_count', 1),
        );
});

it('opens a ticket in the drawer', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = ticketFor($user, ['number' => 5, 'title' => 'Printer']);
    $subtask = ticketFor($user, ['number' => 6, 'parent_id' => $ticket->id]);

    actingAs($user)
        ->get(route('tickets.index', ['ticket' => $ticket->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('drawer.title', 'Printer')
            ->where('drawer.customer_name', 'Acme IT')
            ->where('drawer.subtasks.0.id', $subtask->id)
            ->where('drawer.is_favorite', false)
            ->has('options.labels'),
        );
});

it("keeps the drawer closed for another team's ticket", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('tickets.index', ['ticket' => TicketEloquentModel::factory()->create()->id]))
        ->assertInertia(fn (Assert $page) => $page->where('drawer', null));
});
