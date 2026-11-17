<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldRelationEnum;
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
