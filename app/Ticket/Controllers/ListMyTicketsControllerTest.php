<?php

declare(strict_types=1);

use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("shows the tickets page's views limited to the person's open tickets", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $mine = ['team_id' => $user->current_team_id, 'assignee_id' => $user->id];
    TicketEloquentModel::factory()->create([...$mine, 'title' => 'Mine']);
    TicketEloquentModel::factory()->create([...$mine, 'title' => 'Closed', 'status_id' => ticketStatusId($user->current_team_id, 'Closed')]);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'title' => 'Someone else']);

    actingAs($user)
        ->get(route('tickets.mine'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tickets/mine')
            ->where('filters.assignee', 'me')
            ->where('filters.view', 'list')
            ->has('tickets.data', 1)
            ->where('tickets.data.0.title', 'Mine'),
        );
});

it('keeps to the person whatever assignee the link asks for, and applies the other filters', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $other = UserEloquentModel::factory()->create();
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'assignee_id' => $user->id, 'title' => 'Mine, closed', 'status_id' => ticketStatusId($user->current_team_id, 'Closed')]);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'assignee_id' => $other->id, 'status_id' => ticketStatusId($user->current_team_id, 'Closed')]);

    actingAs($user)
        ->get(route('tickets.mine', ['assignee' => $other->id, 'status' => 'closed', 'view' => 'table']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.assignee', 'me')
            ->where('filters.view', 'table')
            ->has('tickets.data', 1)
            ->where('tickets.data.0.title', 'Mine, closed'),
        );
});
