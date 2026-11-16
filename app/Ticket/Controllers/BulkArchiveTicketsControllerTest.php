<?php

declare(strict_types=1);

use App\Project\ProjectEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('archives every selected ticket', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    [$first, $second, $kept] = TicketEloquentModel::factory()->count(3)->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('tickets.bulk.archive'), ['ticket_ids' => [$first->id, $second->id]])
        ->assertRedirect()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '2 tickets archived.']);

    expect($first->fresh()->archived_at)->not->toBeNull()
        ->and($second->fresh()->archived_at)->not->toBeNull()
        ->and($kept->fresh()->archived_at)->toBeNull();
});

it('needs at least one ticket', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('tickets.bulk.archive'), ['ticket_ids' => []])
        ->assertSessionHasErrors('ticket_ids');
});

it("archives nothing when any ticket is another team's", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $mine = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('tickets.bulk.archive'), ['ticket_ids' => [$mine->id, TicketEloquentModel::factory()->create()->id]])
        ->assertNotFound();

    expect($mine->fresh()->archived_at)->toBeNull();
});

it('archives nothing when any one ticket may not be archived', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'project_id' => ProjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'lead_id' => $member->id])->id]);

    actingAs($member)
        ->post(route('tickets.bulk.archive'), ['ticket_ids' => [$ticket->id]])
        ->assertForbidden();

    expect($ticket->fresh()->archived_at)->toBeNull();
});
