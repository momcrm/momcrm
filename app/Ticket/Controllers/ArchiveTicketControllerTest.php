<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('archives a ticket, taking it out of the list', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'number' => 4]);

    actingAs($user)
        ->post(route('tickets.archive', $ticket->id))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Ticket #4 archived.']);

    expect($ticket->fresh()->archived_at)->not->toBeNull()
        ->and(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::TicketArchived->value)->sole()->subject_id)->toBe($ticket->id);

    actingAs($user)->get(route('tickets.index'))
        ->assertInertia(fn ($page) => $page->has('tickets.data', 0));
});

it("returns not found for another team's ticket", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('tickets.archive', TicketEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
