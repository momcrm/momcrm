<?php

declare(strict_types=1);

use App\Choice\ChoiceEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('moves the tickets in a status to the replacement, then deletes it', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $owner->current_team_id;
    $ticket = TicketEloquentModel::factory()->inStatus('Waiting on supplier')->create(['team_id' => $teamId]);
    $waiting = ticketStatusId($teamId, 'Waiting on supplier');

    actingAs($owner)
        ->delete(route('choices.destroy', $waiting), ['replacement_id' => ticketStatusId($teamId, 'Waiting on customer')])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Waiting on supplier deleted.']);

    expect(ChoiceEloquentModel::query()->withoutGlobalScopes()->find($waiting))->toBeNull()
        ->and($ticket->fresh()->status_id)->toBe(ticketStatusId($teamId, 'Waiting on customer'));
});

it('deletes an unused status without a replacement', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->delete(route('choices.destroy', ticketStatusId($owner->current_team_id, 'Waiting on supplier')))
        ->assertSessionHasNoErrors();
});

it('asks where tickets should go, and only offers the same kind of status', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $owner->current_team_id;
    TicketEloquentModel::factory()->inStatus('In progress')->create(['team_id' => $teamId]);
    $inProgress = ticketStatusId($teamId, 'In progress');

    actingAs($owner)
        ->delete(route('choices.destroy', $inProgress))
        ->assertSessionHasErrors(['replacement_id' => 'Choose where to move the 1 record that uses it.']);

    actingAs($owner)
        ->delete(route('choices.destroy', $inProgress), ['replacement_id' => ticketStatusId($teamId, 'Closed')])
        ->assertSessionHasErrors(['replacement_id' => 'Choose another choice of the same kind to move them to.']);

    expect(ChoiceEloquentModel::query()->withoutGlobalScopes()->find($inProgress))->not->toBeNull();
});

it('keeps the default', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->delete(route('choices.destroy', ticketStatusId($owner->current_team_id, 'Closed')))
        ->assertSessionHasErrors(['choice' => 'Make another choice the default before deleting Closed.']);
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)
        ->delete(route('choices.destroy', ticketStatusId($owner->current_team_id, 'Waiting on supplier')))
        ->assertForbidden();
});
