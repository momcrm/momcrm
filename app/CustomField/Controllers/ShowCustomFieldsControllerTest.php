<?php

declare(strict_types=1);

use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomObject\CustomObjectEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("shows the owner the team's ticket fields in order, archived ones included", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $owner->current_team_id;
    CustomFieldEloquentModel::factory()->archived()->create(['team_id' => $teamId, 'name' => 'Old', 'position' => 1]);
    CustomFieldEloquentModel::factory()->select(['Gold'])->create(['team_id' => $teamId, 'name' => 'Tier', 'position' => 0]);
    CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Customer)->create(['team_id' => $teamId]);
    CustomFieldEloquentModel::factory()->create();

    actingAs($owner)
        ->get(route('custom-fields.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/custom-fields')
            ->where('current', 'ticket')
            ->where('targets.0', ['value' => 'ticket', 'label' => 'Tickets'])
            ->where('types.4', ['value' => 'select', 'label' => 'Select', 'has_options' => true])
            ->where('types.6', ['value' => 'relation', 'label' => 'Record link', 'has_options' => false])
            ->where('relations.1', ['value' => 'customer', 'label' => 'A customer'])
            ->has('fields', 2)
            ->where('fields.0.name', 'Tier')
            ->where('fields.0.type', 'select')
            ->where('fields.0.options.0.name', 'Gold')
            ->where('fields.0.is_archived', false)
            ->where('fields.1.name', 'Old')
            ->where('fields.1.is_archived', true),
        );
});

it('opens the record type named in the query, falling back to tickets', function (string $target, string $current): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->get(route('custom-fields.edit', ['target' => $target]))
        ->assertInertia(fn (Assert $page) => $page->where('current', $current));
})->with([
    'a record type' => ['customer', 'customer'],
    'an unknown one' => ['nope', 'ticket'],
]);

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)->get(route('custom-fields.edit'))->assertForbidden();
});

it("opens a custom object's fields, and lists the team's live objects as tabs", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $owner->current_team_id;
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $teamId, 'name_plural' => 'Vehicles']);
    CustomObjectEloquentModel::factory()->archived()->create(['team_id' => $teamId]);
    CustomFieldEloquentModel::factory()->forObject($vehicles)->create(['name' => 'Plate']);
    CustomFieldEloquentModel::factory()->forObject(CustomObjectEloquentModel::factory()->create(['team_id' => $teamId, 'position' => 1]))->create();
    CustomFieldEloquentModel::factory()->create(['team_id' => $teamId]);

    actingAs($owner)
        ->get(route('custom-fields.edit', ['object' => $vehicles->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('current', 'record')
            ->where('currentObject', $vehicles->id)
            ->has('targets', count(CustomFieldTargetEnum::builtIn()))
            ->has('objects', 2)
            ->where('objects.0', ['value' => $vehicles->id, 'label' => 'Vehicles'])
            ->has('fields', 1)
            ->where('fields.0.name', 'Plate'),
        );
});
