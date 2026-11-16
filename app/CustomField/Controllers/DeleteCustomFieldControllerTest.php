<?php

declare(strict_types=1);

use App\CustomField\CustomFieldEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('deletes an archived field', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $field = CustomFieldEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id, 'name' => 'Region']);

    actingAs($owner)
        ->delete(route('custom-fields.destroy', $field))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Region deleted.']);

    expect($field->fresh())->toBeNull();
});

it('finds no field to delete when it is not archived or belongs to another team', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $live = CustomFieldEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $foreign = CustomFieldEloquentModel::factory()->archived()->create();

    actingAs($owner)->delete(route('custom-fields.destroy', $live))->assertNotFound();
    actingAs($owner)->delete(route('custom-fields.destroy', $foreign))->assertNotFound();

    expect($live->fresh())->not->toBeNull()
        ->and(CustomFieldEloquentModel::query()->withoutGlobalScopes()->find($foreign->id))->not->toBeNull();
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $field = CustomFieldEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)->delete(route('custom-fields.destroy', $field))->assertForbidden();
});
