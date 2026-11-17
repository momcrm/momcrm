<?php

declare(strict_types=1);

use App\CustomObject\Actions\FindCustomObject;
use App\CustomObject\CustomObjectEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it("puts the team's objects in a new order", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $first = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'position' => 0]);
    $second = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'position' => 1]);

    actingAs($owner)
        ->put(route('custom-objects.reorder'), ['ids' => [$second->id, $first->id]])
        ->assertSessionHasNoErrors();

    expect(FindCustomObject::listForTeam($owner->current_team_id)->ids())->toBe([$second->id, $first->id]);
});

it("rejects another team's objects", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $foreign = CustomObjectEloquentModel::factory()->create();

    actingAs($owner)
        ->put(route('custom-objects.reorder'), ['ids' => [$foreign->id]])
        ->assertSessionHasErrors('ids.0');
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $object = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)->put(route('custom-objects.reorder'), ['ids' => [$object->id]])->assertForbidden();
});
