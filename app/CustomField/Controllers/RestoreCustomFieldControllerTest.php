<?php

declare(strict_types=1);

use App\CustomField\CustomFieldEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('restores an archived field', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $field = CustomFieldEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id, 'name' => 'Region']);

    actingAs($owner)
        ->post(route('custom-fields.restore', $field))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Region restored.']);

    expect($field->fresh()->archived_at)->toBeNull();
});

it('finds no field to restore when it is not archived', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $field = CustomFieldEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($owner)->post(route('custom-fields.restore', $field))->assertNotFound();
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $field = CustomFieldEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)->post(route('custom-fields.restore', $field))->assertForbidden();
});
