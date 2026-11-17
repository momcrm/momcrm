<?php

declare(strict_types=1);

use App\CustomObject\CustomObjectEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("shows the owner the team's objects in order, archived ones included", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $owner->current_team_id;
    CustomObjectEloquentModel::factory()->archived()->create(['team_id' => $teamId, 'name_plural' => 'Old things', 'position' => 1]);
    CustomObjectEloquentModel::factory()->create(['team_id' => $teamId, 'name' => 'Vehicle', 'name_plural' => 'Vehicles', 'position' => 0]);
    CustomObjectEloquentModel::factory()->create();

    actingAs($owner)
        ->get(route('custom-objects.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/objects')
            ->has('objects', 2)
            ->where('objects.0.name', 'Vehicle')
            ->where('objects.0.name_plural', 'Vehicles')
            ->where('objects.0.is_archived', false)
            ->where('objects.1.name_plural', 'Old things')
            ->where('objects.1.is_archived', true),
        );
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)->get(route('custom-objects.edit'))->assertForbidden();
});
