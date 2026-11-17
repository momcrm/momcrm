<?php

declare(strict_types=1);

use App\Asset\AssetEloquentModel;
use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomObject\CustomObjectEloquentModel;
use App\Location\LocationEloquentModel;
use App\Project\ProjectEloquentModel;
use App\Record\RecordEloquentModel;
use App\Service\ServiceEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it("lists the team's customers matching the search, by name, to any member", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $owner->current_team_id;
    $member = UserEloquentModel::factory()->inTeam($teamId)->create();
    $field = CustomFieldEloquentModel::factory()->linkingTo(CustomFieldRelationEnum::Customer)->create(['team_id' => $teamId]);
    $zenith = CustomerEloquentModel::factory()->create(['team_id' => $teamId, 'name' => 'Zenith Legal']);
    $acme = CustomerEloquentModel::factory()->create(['team_id' => $teamId, 'name' => 'Acme Legal']);
    CustomerEloquentModel::factory()->create(['team_id' => $teamId, 'name' => 'Bakery']);
    CustomerEloquentModel::factory()->create(['name' => 'Other Legal']);

    actingAs($member)
        ->getJson(route('custom-fields.records', ['customField' => $field->id, 'search' => 'legal']))
        ->assertOk()
        ->assertExactJson(['records' => [
            ['value' => $acme->id, 'label' => 'Acme Legal'],
            ['value' => $zenith->id, 'label' => 'Zenith Legal'],
        ]]);
});

it('lists team members and contacts by their names', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['name' => 'Olive Owner']);
    $teamId = $owner->current_team_id;
    UserEloquentModel::factory()->create(['name' => 'Olive Outsider']);
    $members = CustomFieldEloquentModel::factory()->linkingTo(CustomFieldRelationEnum::Member)->create(['team_id' => $teamId]);
    $contacts = CustomFieldEloquentModel::factory()->linkingTo(CustomFieldRelationEnum::Contact)->create(['team_id' => $teamId]);
    $contact = ContactEloquentModel::factory()->create([
        'team_id' => $teamId,
        'customer_id' => CustomerEloquentModel::factory()->create(['team_id' => $teamId])->id,
        'first_name' => 'Sam',
        'last_name' => 'Lee',
    ]);

    actingAs($owner)
        ->getJson(route('custom-fields.records', ['customField' => $members->id, 'search' => 'olive']))
        ->assertExactJson(['records' => [['value' => $owner->id, 'label' => 'Olive Owner']]]);

    actingAs($owner)
        ->getJson(route('custom-fields.records', ['customField' => $contacts->id, 'search' => 'lee']))
        ->assertExactJson(['records' => [['value' => $contact->id, 'label' => 'Sam Lee']]]);
});

it("finds no records for a field that is not a record link, or is another team's", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $text = CustomFieldEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $foreign = CustomFieldEloquentModel::factory()->linkingTo(CustomFieldRelationEnum::Customer)->create();

    actingAs($owner)->getJson(route('custom-fields.records', $text->id))->assertNotFound();
    actingAs($owner)->getJson(route('custom-fields.records', $foreign->id))->assertNotFound();
});

it("lists the records of the custom object a field links to, and no other object's", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $owner->current_team_id;
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $teamId]);
    $field = CustomFieldEloquentModel::factory()->linkingToObject($vehicles)->create(['team_id' => $teamId]);
    $van = RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Van']);
    RecordEloquentModel::factory()->of(CustomObjectEloquentModel::factory()->create(['team_id' => $teamId]))->create(['name' => 'Van licence']);

    actingAs($owner)
        ->getJson(route('custom-fields.records', ['customField' => $field->id, 'search' => 'van']))
        ->assertExactJson(['records' => [['value' => $van->id, 'label' => 'Van']]]);
});

it('lists tickets by title or number, newest first, and projects by name', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $owner->current_team_id];
    $tickets = CustomFieldEloquentModel::factory()->linkingTo(CustomFieldRelationEnum::Ticket)->create($team);
    $projects = CustomFieldEloquentModel::factory()->linkingTo(CustomFieldRelationEnum::Project)->create($team);
    $old = TicketEloquentModel::factory()->create([...$team, 'number' => 7, 'title' => 'Printer offline']);
    $new = TicketEloquentModel::factory()->create([...$team, 'number' => 12, 'title' => 'Printer jam']);
    TicketEloquentModel::factory()->create([...$team, 'number' => 13, 'title' => 'Slow laptop']);
    $refit = ProjectEloquentModel::factory()->create([...$team, 'name' => 'Office refit']);

    actingAs($owner)
        ->getJson(route('custom-fields.records', ['customField' => $tickets->id, 'search' => 'printer']))
        ->assertExactJson(['records' => [
            ['value' => $new->id, 'label' => '#12 Printer jam'],
            ['value' => $old->id, 'label' => '#7 Printer offline'],
        ]]);

    actingAs($owner)
        ->getJson(route('custom-fields.records', ['customField' => $tickets->id, 'search' => '#7']))
        ->assertExactJson(['records' => [['value' => $old->id, 'label' => '#7 Printer offline']]]);

    actingAs($owner)
        ->getJson(route('custom-fields.records', ['customField' => $projects->id, 'search' => 'refit']))
        ->assertExactJson(['records' => [['value' => $refit->id, 'label' => 'Office refit']]]);
});

it('offers tickets and projects in a members-only project only to its members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $owner->current_team_id];
    $outsider = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $insider = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $tickets = CustomFieldEloquentModel::factory()->linkingTo(CustomFieldRelationEnum::Ticket)->create($team);
    $projects = CustomFieldEloquentModel::factory()->linkingTo(CustomFieldRelationEnum::Project)->create($team);
    $secret = ProjectEloquentModel::factory()->create([...$team, 'name' => 'Merger', 'visibility' => 'restricted']);
    $secret->members()->attach($insider->id);
    $ticket = TicketEloquentModel::factory()->create([...$team, 'project_id' => $secret->id, 'number' => 3, 'title' => 'Due diligence']);

    actingAs($outsider)->getJson(route('custom-fields.records', $tickets->id))->assertExactJson(['records' => []]);
    actingAs($outsider)->getJson(route('custom-fields.records', $projects->id))->assertExactJson(['records' => []]);
    actingAs($insider)->getJson(route('custom-fields.records', $tickets->id))->assertExactJson(['records' => [['value' => $ticket->id, 'label' => '#3 Due diligence']]]);
    actingAs($insider)->getJson(route('custom-fields.records', $projects->id))->assertExactJson(['records' => [['value' => $secret->id, 'label' => 'Merger']]]);
});

it("lists the team's assets, locations and services by name", function (CustomFieldRelationEnum $kind, string $model): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $owner->current_team_id];
    $field = CustomFieldEloquentModel::factory()->linkingTo($kind)->create($team);
    $mine = $model::factory()->create([...$team, 'customer_id' => CustomerEloquentModel::factory()->create($team)->id, 'name' => 'Harbour one']);
    $model::factory()->create(['name' => 'Harbour elsewhere']);

    actingAs($owner)
        ->getJson(route('custom-fields.records', ['customField' => $field->id, 'search' => 'harbour']))
        ->assertExactJson(['records' => [['value' => $mine->id, 'label' => 'Harbour one']]]);
})->with([
    'assets' => [CustomFieldRelationEnum::Asset, AssetEloquentModel::class],
    'locations' => [CustomFieldRelationEnum::Location, LocationEloquentModel::class],
    'services' => [CustomFieldRelationEnum::Service, ServiceEloquentModel::class],
]);
