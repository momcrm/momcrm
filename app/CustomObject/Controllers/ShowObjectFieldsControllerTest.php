<?php

declare(strict_types=1);

use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomObject\CustomObjectEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("shows a built-in object's own fields and the team's custom fields on it, in order, archived ones included", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $owner->current_team_id;
    CustomFieldEloquentModel::factory()->archived()->create(['team_id' => $teamId, 'name' => 'Old', 'position' => 1]);
    CustomFieldEloquentModel::factory()->select(['Gold'])->create(['team_id' => $teamId, 'name' => 'Tier', 'position' => 0]);
    CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Customer)->create(['team_id' => $teamId]);
    CustomFieldEloquentModel::factory()->create();
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $teamId, 'name_plural' => 'Vehicles']);

    actingAs($owner)
        ->get(route('custom-objects.fields', 'ticket'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/object-fields')
            ->where('object', ['key' => 'ticket', 'label' => 'Tickets', 'icon' => 'ticket', 'hue' => null, 'is_standard' => true])
            ->where('current', 'ticket')
            ->where('currentObject', null)
            ->where('builtInFields.0', ['name' => 'Title', 'type' => 'Text'])
            ->where('types.4', ['value' => 'select', 'label' => 'Select', 'has_options' => true])
            ->where('relations.1', ['value' => 'customer', 'label' => 'A customer'])
            ->where('objects', [['value' => $vehicles->id, 'label' => 'Vehicles']])
            ->has('fields', 2)
            ->where('fields.0.name', 'Tier')
            ->where('fields.0.options.0.name', 'Gold')
            ->where('fields.0.is_archived', false)
            ->where('fields.1.name', 'Old')
            ->where('fields.1.is_archived', true),
        );
});

it("shows a custom object's fields, whose only built-in field is its name", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $owner->current_team_id;
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $teamId, 'name_plural' => 'Vehicles', 'icon' => 'truck', 'hue' => 120]);
    CustomFieldEloquentModel::factory()->forObject($vehicles)->create(['name' => 'Plate']);
    CustomFieldEloquentModel::factory()->forObject(CustomObjectEloquentModel::factory()->create(['team_id' => $teamId, 'position' => 1]))->create();
    CustomFieldEloquentModel::factory()->create(['team_id' => $teamId]);

    actingAs($owner)
        ->get(route('custom-objects.fields', $vehicles->id))
        ->assertInertia(fn (Assert $page) => $page
            ->where('object', ['key' => $vehicles->id, 'label' => 'Vehicles', 'icon' => 'truck', 'hue' => 120, 'is_standard' => false])
            ->where('current', 'record')
            ->where('currentObject', $vehicles->id)
            ->where('builtInFields', [['name' => 'Name', 'type' => 'Text']])
            ->has('fields', 1)
            ->where('fields.0.name', 'Plate'),
        );
});

it("finds no fields page for an archived object, another team's, or an unknown kind", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $archived = CustomObjectEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id]);
    $foreign = CustomObjectEloquentModel::factory()->create();

    actingAs($owner)->get(route('custom-objects.fields', $archived->id))->assertNotFound();
    actingAs($owner)->get(route('custom-objects.fields', $foreign->id))->assertNotFound();
    actingAs($owner)->get(route('custom-objects.fields', 'payment'))->assertNotFound();
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)->get(route('custom-objects.fields', 'ticket'))->assertForbidden();
});
