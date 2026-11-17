<?php

declare(strict_types=1);

use App\CustomObject\CustomObjectEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('changes only what is sent', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $object = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name' => 'Vehicle', 'name_plural' => 'Vehicles', 'icon' => 'truck']);

    actingAs($owner)
        ->patch(route('custom-objects.update', $object), ['name_plural' => 'Fleet', 'hue' => null])
        ->assertSessionHasNoErrors();

    expect($object->fresh())
        ->name->toBe('Vehicle')
        ->name_plural->toBe('Fleet')
        ->icon->toBe('truck')
        ->hue->toBeNull();
});

it('lets an object keep its own plural name but not take another', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name_plural' => 'Licences']);
    $object = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name_plural' => 'Vehicles']);

    actingAs($owner)
        ->patch(route('custom-objects.update', $object), ['name_plural' => 'Vehicles'])
        ->assertSessionHasNoErrors();

    actingAs($owner)
        ->patch(route('custom-objects.update', $object), ['name_plural' => 'Licences'])
        ->assertSessionHasErrors(['name_plural' => 'You already have an object with that name, or one in the archive.']);
});

it("hides another team's object", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $object = CustomObjectEloquentModel::factory()->create();

    actingAs($owner)->patch(route('custom-objects.update', $object), ['name' => 'Mine'])->assertNotFound();
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $object = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)->patch(route('custom-objects.update', $object), ['name' => 'Mine'])->assertForbidden();
});
