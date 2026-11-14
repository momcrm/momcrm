<?php

declare(strict_types=1);

use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('deletes an archived ticket for good', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->archived()->create(['team_id' => $user->current_team_id, 'number' => 4]);

    actingAs($user)
        ->delete(route('tickets.destroy', $ticket->id))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Ticket #4 deleted.']);

    expect(TicketEloquentModel::query()->exists())->toBeFalse();
});

it('only deletes tickets from the archive', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)
        ->delete(route('tickets.destroy', TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id])->id))
        ->assertNotFound();
});
