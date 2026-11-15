<?php

declare(strict_types=1);

use App\Choice\ChoiceEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('restores an archived status', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $waiting = ticketStatusId($owner->current_team_id, 'Waiting on supplier');
    ChoiceEloquentModel::query()->withoutGlobalScopes()->whereKey($waiting)->update(['archived_at' => now()]);

    actingAs($owner)
        ->post(route('choices.restore', $waiting))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Waiting on supplier restored.']);

    expect(ChoiceEloquentModel::query()->withoutGlobalScopes()->find($waiting)->archived_at)->toBeNull();
});

it('returns not found for a choice that is not archived', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->post(route('choices.restore', ticketStatusId($owner->current_team_id, 'Waiting on supplier')))
        ->assertNotFound();
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $waiting = ticketStatusId($owner->current_team_id, 'Waiting on supplier');
    ChoiceEloquentModel::query()->withoutGlobalScopes()->whereKey($waiting)->update(['archived_at' => now()]);

    actingAs($member)
        ->post(route('choices.restore', $waiting))
        ->assertForbidden();
});
