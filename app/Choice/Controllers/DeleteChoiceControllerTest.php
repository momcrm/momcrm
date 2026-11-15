<?php

declare(strict_types=1);

use App\Choice\ChoiceEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

function archiveTicketStatus(string $teamId, string $name): string
{
    $id = ticketStatusId($teamId, $name);
    ChoiceEloquentModel::query()->withoutGlobalScopes()->whereKey($id)->update(['archived_at' => now()]);

    return $id;
}

it('deletes an archived status nothing uses', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $waiting = archiveTicketStatus($owner->current_team_id, 'Waiting on supplier');

    actingAs($owner)
        ->delete(route('choices.destroy', $waiting))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Waiting on supplier deleted.']);

    expect(ChoiceEloquentModel::query()->withoutGlobalScopes()->find($waiting))->toBeNull();
});

it('keeps an archived status that tickets still use', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    TicketEloquentModel::factory()->inStatus('Waiting on supplier')->create(['team_id' => $owner->current_team_id]);
    $waiting = archiveTicketStatus($owner->current_team_id, 'Waiting on supplier');

    actingAs($owner)
        ->delete(route('choices.destroy', $waiting))
        ->assertSessionHasErrors(['choice' => 'Waiting on supplier is still used by 1 record, so it stays archived.']);

    expect(ChoiceEloquentModel::query()->withoutGlobalScopes()->find($waiting))->not->toBeNull();
});

it('returns not found for a choice that is not archived', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->delete(route('choices.destroy', ticketStatusId($owner->current_team_id, 'Waiting on supplier')))
        ->assertNotFound();
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)
        ->delete(route('choices.destroy', archiveTicketStatus($owner->current_team_id, 'Waiting on supplier')))
        ->assertForbidden();
});
