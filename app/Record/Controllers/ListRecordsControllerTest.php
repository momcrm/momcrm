<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldTypeEnum;
use App\CustomObject\CustomObjectEloquentModel;
use App\Record\RecordEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists one object's records by name, with what a member may do", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create(['onboarded_at' => now()]);
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name_plural' => 'Vehicles']);
    RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Van', 'number' => 1]);
    RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Bike', 'number' => 2]);
    RecordEloquentModel::factory()->of(CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]))->create();

    actingAs($member)
        ->get(route('records.index', $vehicles))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('objects/show')
            ->where('object.name_plural', 'Vehicles')
            ->where('records.total', 2)
            ->where('records.data.0.name', 'Bike')
            ->where('records.data.1.name', 'Van')
            ->where('filters', ['search' => null, 'sort' => 'name', 'direction' => 'asc', 'custom_fields' => []])
            ->where('can', ['create' => true, 'update' => true, 'delete' => false]),
        );
});

it('searches by name and sorts by number', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Red van', 'number' => 1]);
    RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Blue van', 'number' => 2]);
    RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Bike', 'number' => 3]);

    actingAs($owner)
        ->get(route('records.index', ['object' => $vehicles->id, 'search' => 'van', 'sort' => 'number', 'direction' => 'desc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('records.total', 2)
            ->where('records.data.0.name', 'Blue van')
            ->where('records.data.1.name', 'Red van'),
        );
});

it("finds no list for an archived object or another team's", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $archived = CustomObjectEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id]);
    $foreign = CustomObjectEloquentModel::factory()->create();

    actingAs($owner)->get(route('records.index', $archived))->assertNotFound();
    actingAs($owner)->get(route('records.index', $foreign))->assertNotFound();
});

it("filters and sorts by the object's fields, and sends them with the names of linked records", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $teamId = $owner->current_team_id;
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $teamId]);
    $leased = CustomFieldEloquentModel::factory()->forObject($vehicles)->ofType(CustomFieldTypeEnum::Checkbox)->create(['position' => 0]);
    $seats = CustomFieldEloquentModel::factory()->forObject($vehicles)->ofType(CustomFieldTypeEnum::Number)->create(['position' => 1]);
    $lessor = CustomFieldEloquentModel::factory()->forObject($vehicles)->linkingTo(CustomFieldRelationEnum::Customer)->create(['position' => 2]);
    CustomFieldEloquentModel::factory()->create(['team_id' => $teamId]);
    $bank = CustomerEloquentModel::factory()->create(['team_id' => $teamId, 'name' => 'Fleet Bank']);
    RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Minibus', 'custom_fields' => [$leased->id => true, $seats->id => 12, $lessor->id => $bank->id]]);
    RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Van', 'custom_fields' => [$leased->id => true, $seats->id => 3]]);
    RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Bike', 'custom_fields' => [$seats->id => 1]]);

    actingAs($owner)
        ->get(route('records.index', ['object' => $vehicles->id, 'cf' => [$leased->id => '1'], 'sort' => "cf:{$seats->id}", 'direction' => 'desc']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('fields', 3)
            ->where('fields.0.id', $leased->id)
            ->where('filters.custom_fields', [$leased->id => '1'])
            ->where('filters.sort', "cf:{$seats->id}")
            ->where('records.total', 2)
            ->where('records.data.0.name', 'Minibus')
            ->where('records.data.0.custom_field_labels', [$bank->id => 'Fleet Bank'])
            ->where('records.data.1.name', 'Van'),
        );
});
