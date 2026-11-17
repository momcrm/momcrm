<?php

declare(strict_types=1);

use App\CustomObject\CustomObjectEloquentModel;
use App\Record\RecordEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows a record with its object and what the viewer may do', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name' => 'Vehicle']);
    $record = RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Van', 'number' => 7]);

    actingAs($owner)
        ->get(route('records.show', [$vehicles, $record]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('objects/record')
            ->where('object.name', 'Vehicle')
            ->where('record.name', 'Van')
            ->where('record.number', 7)
            ->where('can', ['create' => true, 'update' => true, 'delete' => true]),
        );
});

it('finds no record under another object, or in another team', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $licences = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $licence = RecordEloquentModel::factory()->of($licences)->create();
    $foreign = RecordEloquentModel::factory()->create();

    actingAs($owner)->get(route('records.show', [$vehicles, $licence]))->assertNotFound();
    actingAs($owner)->get(route('records.show', [$vehicles, $foreign]))->assertNotFound();
    actingAs($owner)->get(route('records.show', [$foreign->object_id, $foreign]))->assertNotFound();
});
