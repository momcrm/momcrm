<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomObject\CustomObjectEloquentModel;
use App\Record\RecordEloquentModel;
use App\Ticket\TicketEloquentModel;
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

it("shows a record's fields and values, with the names of the records it links to", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $teamId = $owner->current_team_id;
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $teamId]);
    $lessor = CustomFieldEloquentModel::factory()->forObject($vehicles)->linkingTo(CustomFieldRelationEnum::Customer)->create(['name' => 'Lessor']);
    CustomFieldEloquentModel::factory()->forObject($vehicles)->archived()->create();
    $bank = CustomerEloquentModel::factory()->create(['team_id' => $teamId, 'name' => 'Fleet Bank']);
    $record = RecordEloquentModel::factory()->of($vehicles)->create(['custom_fields' => [$lessor->id => $bank->id]]);

    actingAs($owner)
        ->get(route('records.show', [$vehicles, $record]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('fields', 1)
            ->where('fields.0.name', 'Lessor')
            ->where("record.custom_fields.{$lessor->id}", $bank->id)
            ->where('customFieldLabels', [$bank->id => 'Fleet Bank']),
        );
});

it('lists what links to the record', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $teamId = $owner->current_team_id;
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $teamId]);
    $van = RecordEloquentModel::factory()->of($vehicles)->create();
    $field = CustomFieldEloquentModel::factory()->linkingToObject($vehicles)->create(['team_id' => $teamId, 'name' => 'Vehicle']);
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $teamId, 'number' => 7, 'title' => 'Flat tyre', 'custom_fields' => [$field->id => $van->id]]);

    actingAs($owner)
        ->get(route('records.show', [$vehicles, $van]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('linkedRecords', 1)
            ->where('linkedRecords.0.source', 'Tickets')
            ->where('linkedRecords.0.field', 'Vehicle')
            ->where('linkedRecords.0.count', 1)
            ->where('linkedRecords.0.records.0', ['id' => $ticket->id, 'title' => '#7 Flat tyre', 'url' => route('tickets.show', $ticket->id)]),
        );
});
