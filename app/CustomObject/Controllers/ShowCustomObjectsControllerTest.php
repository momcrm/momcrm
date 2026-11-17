<?php

declare(strict_types=1);

use App\CustomField\CustomFieldEloquentModel;
use App\CustomObject\CustomObjectEloquentModel;
use App\CustomObject\StandardObjectEnum;
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

it('lists the built-in objects before the team\'s own, each with how many custom fields it has', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $owner->current_team_id;
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $teamId]);
    CustomFieldEloquentModel::factory()->count(2)->create(['team_id' => $teamId]);
    CustomFieldEloquentModel::factory()->archived()->create(['team_id' => $teamId]);
    CustomFieldEloquentModel::factory()->forObject($vehicles)->create();
    CustomFieldEloquentModel::factory()->create();

    actingAs($owner)
        ->get(route('custom-objects.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('standardObjects', count(StandardObjectEnum::cases()))
            ->where('standardObjects.0', ['key' => 'ticket', 'label' => 'Tickets', 'icon' => 'ticket', 'fields_count' => 15, 'custom_fields_count' => 2])
            ->where('standardObjects.1.key', 'customer')
            ->where('standardObjects.1.custom_fields_count', 0)
            ->where('objects.0.custom_fields_count', 1),
        );
});
