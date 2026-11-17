<?php

declare(strict_types=1);

use App\CustomObject\CustomObjectEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('archives an object', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $object = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name_plural' => 'Vehicles']);

    actingAs($owner)
        ->post(route('custom-objects.archive', $object))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Vehicles archived.']);

    expect($object->fresh()->archived_at)->not->toBeNull();
});

it('finds no object to archive when it is already archived or belongs to another team', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $archived = CustomObjectEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id]);
    $foreign = CustomObjectEloquentModel::factory()->create();

    actingAs($owner)->post(route('custom-objects.archive', $archived))->assertNotFound();
    actingAs($owner)->post(route('custom-objects.archive', $foreign))->assertNotFound();

    expect($foreign->fresh()->archived_at)->toBeNull();
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $object = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)->post(route('custom-objects.archive', $object))->assertForbidden();
});
