<?php

declare(strict_types=1);

use App\CustomField\CustomFieldEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('archives a field', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $field = CustomFieldEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name' => 'Region']);

    actingAs($owner)
        ->post(route('custom-fields.archive', $field))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Region archived.']);

    expect($field->fresh()->archived_at)->not->toBeNull();
});

it('finds no field to archive when it is already archived or belongs to another team', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $archived = CustomFieldEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id]);
    $foreign = CustomFieldEloquentModel::factory()->create();

    actingAs($owner)->post(route('custom-fields.archive', $archived))->assertNotFound();
    actingAs($owner)->post(route('custom-fields.archive', $foreign))->assertNotFound();

    expect($foreign->fresh()->archived_at)->toBeNull();
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $field = CustomFieldEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)->post(route('custom-fields.archive', $field))->assertForbidden();
});
