<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
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
