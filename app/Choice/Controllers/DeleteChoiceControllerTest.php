<?php

declare(strict_types=1);

use App\Asset\AssetEloquentModel;
use App\Choice\Actions\FindChoice;
use App\Choice\ChoiceEloquentModel;
use App\Choice\ChoiceListEnum;
use App\Project\ProjectEloquentModel;
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

it('deletes a label straight away, taking it off its tickets', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $label = ChoiceEloquentModel::factory()->label()->create(['team_id' => $owner->current_team_id, 'name' => 'Bug']);
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $ticket->labels()->attach($label);

    actingAs($owner)
        ->delete(route('choices.destroy', $label->id))
        ->assertSessionHasNoErrors();

    expect(ChoiceEloquentModel::query()->withoutGlobalScopes()->find($label->id))->toBeNull()
        ->and($ticket->labels()->count())->toBe(0);
});

it('deletes a ticket type straight away, clearing it from its tickets', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $type = FindChoice::byNameForTeam($owner->current_team_id, ChoiceListEnum::TicketType, 'Question');
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'type_id' => $type->getId()->toString()]);

    actingAs($owner)
        ->delete(route('choices.destroy', $type->getId()->toString()))
        ->assertSessionHasNoErrors();

    expect(ChoiceEloquentModel::query()->withoutGlobalScopes()->find($type->getId()->toString()))->toBeNull()
        ->and($ticket->fresh()->type_id)->toBeNull();
});

it('takes a deleted label off projects and assets too', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $label = ChoiceEloquentModel::factory()->label()->create(['team_id' => $owner->current_team_id]);
    $project = ProjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $asset = AssetEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $project->labels()->attach($label);
    $asset->labels()->attach($label);

    actingAs($owner)->delete(route('choices.destroy', $label->id))->assertSessionHasNoErrors();

    expect($project->labels()->count())->toBe(0)
        ->and($asset->labels()->count())->toBe(0);
});
