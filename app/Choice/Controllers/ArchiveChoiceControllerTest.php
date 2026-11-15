<?php

declare(strict_types=1);

use App\Choice\ChoiceEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('archives a status, leaving its tickets in it', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $owner->current_team_id;
    $ticket = TicketEloquentModel::factory()->inStatus('Waiting on supplier')->create(['team_id' => $teamId]);
    $waiting = ticketStatusId($teamId, 'Waiting on supplier');

    actingAs($owner)
        ->post(route('choices.archive', $waiting))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Waiting on supplier archived.']);

    expect(ChoiceEloquentModel::query()->withoutGlobalScopes()->find($waiting)->archived_at)->not->toBeNull()
        ->and($ticket->fresh()->status_id)->toBe($waiting);
});

it('moves the tickets to a replacement of the same kind when asked', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $owner->current_team_id;
    $ticket = TicketEloquentModel::factory()->inStatus('In progress')->create(['team_id' => $teamId]);
    $inProgress = ticketStatusId($teamId, 'In progress');

    actingAs($owner)
        ->post(route('choices.archive', $inProgress), ['replacement_id' => ticketStatusId($teamId, 'Closed')])
        ->assertSessionHasErrors(['replacement_id' => 'Choose another choice of the same kind to move them to.']);

    expect($ticket->fresh()->status_id)->toBe($inProgress);

    actingAs($owner)
        ->post(route('choices.archive', $inProgress), ['replacement_id' => ticketStatusId($teamId, 'Waiting on customer')])
        ->assertSessionHasNoErrors();

    expect($ticket->fresh()->status_id)->toBe(ticketStatusId($teamId, 'Waiting on customer'));
});

it('keeps the default', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->post(route('choices.archive', ticketStatusId($owner->current_team_id, 'Closed')))
        ->assertSessionHasErrors(['choice' => 'Make another choice the default before archiving Closed.']);
});

it('returns not found for a choice that is already archived', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $waiting = ticketStatusId($owner->current_team_id, 'Waiting on supplier');
    ChoiceEloquentModel::query()->withoutGlobalScopes()->whereKey($waiting)->update(['archived_at' => now()]);

    actingAs($owner)
        ->post(route('choices.archive', $waiting))
        ->assertNotFound();
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)
        ->post(route('choices.archive', ticketStatusId($owner->current_team_id, 'Waiting on supplier')))
        ->assertForbidden();
});

it('archives any label, keeping it on its tickets, and never moves them', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    [$bug, $feature] = ChoiceEloquentModel::factory()->label()->count(2)->create(['team_id' => $owner->current_team_id])->all();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $ticket->labels()->attach($bug);

    actingAs($owner)
        ->post(route('choices.archive', $bug->id), ['replacement_id' => $feature->id])
        ->assertSessionHasErrors('replacement_id');

    actingAs($owner)
        ->post(route('choices.archive', $bug->id))
        ->assertSessionHasNoErrors();

    actingAs($owner)
        ->post(route('choices.archive', $feature->id))
        ->assertSessionHasNoErrors();

    expect($ticket->labels()->pluck('choices.id')->all())->toBe([$bug->id]);
});
