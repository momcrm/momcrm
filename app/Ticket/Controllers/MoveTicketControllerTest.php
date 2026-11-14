<?php

declare(strict_types=1);

use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('moves a ticket to another column at the given place', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $first = TicketEloquentModel::factory()->create([...$team, 'status' => 'in_progress', 'position' => 0]);
    $second = TicketEloquentModel::factory()->create([...$team, 'status' => 'in_progress', 'position' => 1]);
    $moving = TicketEloquentModel::factory()->create([...$team, 'status' => 'new', 'position' => 0]);

    actingAs($user)
        ->from(route('tickets.index'))
        ->put(route('tickets.move', $moving->id), ['status' => 'in_progress', 'position' => 1])
        ->assertRedirectToRoute('tickets.index');

    expect($moving->fresh())->status->toBe('in_progress')->position->toBe(1)
        ->and($first->fresh()->position)->toBe(0)
        ->and($second->fresh()->position)->toBe(2);
});

it('reorders a ticket within its column', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id, 'status' => 'new'];
    $tickets = collect(range(0, 2))->map(fn (int $position) => TicketEloquentModel::factory()->create([...$team, 'position' => $position]));

    actingAs($user)->put(route('tickets.move', $tickets[2]->id), ['status' => 'new', 'position' => 0]);

    expect($tickets->map(fn (TicketEloquentModel $ticket): int => $ticket->fresh()->position)->all())->toBe([1, 2, 0]);
});

it('stamps the closed time when dropped into Closed', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)->put(route('tickets.move', $ticket->id), ['status' => 'closed', 'position' => 0]);

    expect($ticket->fresh()->closed_at)->not->toBeNull();
});

it('rejects an unknown status', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->put(route('tickets.move', $ticket->id), ['status' => 'done', 'position' => 0])
        ->assertSessionHasErrors('status');
});

it("returns not found for another team's ticket", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->put(route('tickets.move', TicketEloquentModel::factory()->create()->id), ['status' => 'new', 'position' => 0])
        ->assertNotFound();
});
