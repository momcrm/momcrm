<?php

declare(strict_types=1);

use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows the edit form for a ticket', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->get(route('tickets.edit', $ticket->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('tickets/edit')->where('ticket.id', $ticket->id));
});

it("returns not found for another team's ticket", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('tickets.edit', TicketEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
