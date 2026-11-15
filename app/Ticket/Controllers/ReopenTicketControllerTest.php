<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it("reopens a closed ticket into the team's default open status and clears its closed time", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->closed()->create([
        'team_id' => $user->current_team_id,
        'closed_at' => now(),
    ]);

    actingAs($user)
        ->post(route('tickets.reopen', $ticket->id))
        ->assertRedirectToRoute('tickets.show', $ticket->id);

    expect($ticket->fresh())
        ->status_id->toBe(ticketStatusId($user->current_team_id, 'New'))
        ->closed_at->toBeNull();
    expect(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::TicketReopened->value)->exists())->toBeTrue();
});
