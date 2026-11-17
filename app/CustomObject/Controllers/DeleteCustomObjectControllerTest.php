<?php

declare(strict_types=1);

use App\CustomObject\CustomObjectEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('deletes an archived object', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $object = CustomObjectEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id, 'name_plural' => 'Vehicles']);

    actingAs($owner)
        ->delete(route('custom-objects.destroy', $object))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Vehicles deleted.']);

    expect($object->fresh())->toBeNull();
});

it('finds no object to delete when it is not archived or belongs to another team', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $live = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $foreign = CustomObjectEloquentModel::factory()->archived()->create();

    actingAs($owner)->delete(route('custom-objects.destroy', $live))->assertNotFound();
    actingAs($owner)->delete(route('custom-objects.destroy', $foreign))->assertNotFound();

    expect($live->fresh())->not->toBeNull()
        ->and(CustomObjectEloquentModel::query()->withoutGlobalScopes()->find($foreign->id))->not->toBeNull();
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $object = CustomObjectEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)->delete(route('custom-objects.destroy', $object))->assertForbidden();
});
