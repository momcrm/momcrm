<?php

declare(strict_types=1);

use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('restores an archived ticket', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->archived()->create(['team_id' => $user->current_team_id, 'number' => 4]);

    actingAs($user)
        ->post(route('tickets.restore', $ticket->id))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Ticket #4 restored.']);

    expect($ticket->fresh()->archived_at)->toBeNull();
});

it('returns not found for a ticket that is not archived', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)
        ->post(route('tickets.restore', TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id])->id))
        ->assertNotFound();
});

it("returns not found for another team's ticket", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('tickets.restore', TicketEloquentModel::factory()->archived()->create()->id))
        ->assertNotFound();
});
