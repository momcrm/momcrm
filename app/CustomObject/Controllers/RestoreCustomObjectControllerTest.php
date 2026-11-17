<?php

declare(strict_types=1);

use App\CustomObject\CustomObjectEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('restores an archived object', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $object = CustomObjectEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id, 'name_plural' => 'Vehicles']);

    actingAs($owner)
        ->post(route('custom-objects.restore', $object))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Vehicles restored.']);

    expect($object->fresh()->archived_at)->toBeNull();
});

it('finds no object to restore when it is not archived', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $object = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($owner)->post(route('custom-objects.restore', $object))->assertNotFound();
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $object = CustomObjectEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)->post(route('custom-objects.restore', $object))->assertForbidden();
});
