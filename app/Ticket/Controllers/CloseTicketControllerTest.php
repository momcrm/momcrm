<?php

declare(strict_types=1);

use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('closes a ticket', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'number' => 7]);

    actingAs($user)
        ->post(route('tickets.close', $ticket->id))
        ->assertRedirectToRoute('tickets.show', $ticket->id)
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Ticket #7 closed. Nice work.']);

    expect($ticket->fresh())
        ->status_id->toBe(ticketStatusId($user->current_team_id, 'Closed'))
        ->closed_at->not->toBeNull();
});

it("returns not found for another team's ticket", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('tickets.close', TicketEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
