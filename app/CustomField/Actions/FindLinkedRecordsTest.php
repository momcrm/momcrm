<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomField\Actions\FindLinkedRecords;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomObject\CustomObjectEloquentModel;
use App\Project\ProjectEloquentModel;
use App\Record\RecordEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

it('finds what links to a customer from every kind of record, grouped by field', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $owner->current_team_id;
    $team = ['team_id' => $teamId];
    $acme = CustomerEloquentModel::factory()->create([...$team, 'name' => 'Acme']);
    $other = CustomerEloquentModel::factory()->create($team);
    $vehicles = CustomObjectEloquentModel::factory()->create([...$team, 'name_plural' => 'Vehicles']);
    $link = fn (CustomFieldTargetEnum $target, string $name, int $position) => CustomFieldEloquentModel::factory()
        ->on($target)->linkingTo(CustomFieldRelationEnum::Customer)->create([...$team, 'name' => $name, 'position' => $position]);

    $reseller = $link(CustomFieldTargetEnum::Ticket, 'Reseller', 0);
    $parent = $link(CustomFieldTargetEnum::Customer, 'Parent company', 1);
    $employer = $link(CustomFieldTargetEnum::Contact, 'Also works for', 2);
    $sponsor = $link(CustomFieldTargetEnum::Project, 'Sponsor', 3);
    $lessor = CustomFieldEloquentModel::factory()->forObject($vehicles)->linkingTo(CustomFieldRelationEnum::Customer)->create(['name' => 'Lessor', 'position' => 4]);

    $ticket = TicketEloquentModel::factory()->create([...$team, 'customer_id' => $other->id, 'number' => 3, 'title' => 'Resold laptop', 'custom_fields' => [$reseller->id => $acme->id]]);
    TicketEloquentModel::factory()->create([...$team, 'customer_id' => $other->id, 'custom_fields' => [$reseller->id => $other->id]]);
    $child = CustomerEloquentModel::factory()->create([...$team, 'name' => 'Acme Retail', 'custom_fields' => [$parent->id => $acme->id]]);
    $contact = ContactEloquentModel::factory()->create([...$team, 'customer_id' => $other->id, 'first_name' => 'Sam', 'last_name' => 'Lee', 'custom_fields' => [$employer->id => $acme->id]]);
    $project = ProjectEloquentModel::factory()->create([...$team, 'name' => 'Refit', 'custom_fields' => [$sponsor->id => $acme->id]]);
    $van = RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Van', 'custom_fields' => [$lessor->id => $acme->id]]);

    $groups = FindLinkedRecords::to($teamId, $owner->id, CustomFieldRelationEnum::Customer, $acme->id);

    expect(array_map(fn (array $group): array => [$group['source'], $group['field'], $group['count'], array_column($group['records'], 'title'), array_column($group['records'], 'url')], $groups))->toBe([
        ['Tickets', 'Reseller', 1, ['#3 Resold laptop'], [route('tickets.show', $ticket->id)]],
        ['Customers', 'Parent company', 1, ['Acme Retail'], [route('customers.show', $child->id)]],
        ['Contacts', 'Also works for', 1, ['Sam Lee'], [route('contacts.edit', $contact->id)]],
        ['Projects', 'Sponsor', 1, ['Refit'], [route('projects.show', $project->id)]],
        ['Vehicles', 'Lessor', 1, ['Van'], [route('records.show', [$vehicles->id, $van->id])]],
    ]);
});

it("keeps links to another custom object's records apart", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $owner->current_team_id;
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $teamId]);
    $licences = CustomObjectEloquentModel::factory()->create(['team_id' => $teamId]);
    $van = RecordEloquentModel::factory()->of($vehicles)->create();
    $toVehicle = CustomFieldEloquentModel::factory()->linkingToObject($vehicles)->create(['team_id' => $teamId]);
    $toLicence = CustomFieldEloquentModel::factory()->linkingToObject($licences)->create(['team_id' => $teamId]);
    TicketEloquentModel::factory()->create(['team_id' => $teamId, 'custom_fields' => [$toVehicle->id => $van->id]]);
    TicketEloquentModel::factory()->create(['team_id' => $teamId, 'custom_fields' => [$toLicence->id => $van->id]]);

    expect(FindLinkedRecords::to($teamId, $owner->id, CustomFieldRelationEnum::Record, $van->id, $vehicles->id))
        ->toHaveCount(1)
        ->{0}->key->toBe($toVehicle->id);
});

it('lists the newest few of many links and counts them all', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $owner->current_team_id;
    $acme = CustomerEloquentModel::factory()->create(['team_id' => $teamId]);
    $field = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Customer)->linkingTo(CustomFieldRelationEnum::Customer)->create(['team_id' => $teamId]);
    CustomerEloquentModel::factory()->count(12)->create(['team_id' => $teamId, 'custom_fields' => [$field->id => $acme->id]]);

    $groups = FindLinkedRecords::to($teamId, $owner->id, CustomFieldRelationEnum::Customer, $acme->id);

    expect($groups[0]['count'])->toBe(12)
        ->and($groups[0]['records'])->toHaveCount(FindLinkedRecords::PER_GROUP);
});

it("leaves out tickets in a members-only project someone isn't in, archived fields, archived objects and other teams", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $owner->current_team_id;
    $member = UserEloquentModel::factory()->inTeam($teamId)->create();
    $team = ['team_id' => $teamId];
    $acme = CustomerEloquentModel::factory()->create($team);
    $live = CustomFieldEloquentModel::factory()->linkingTo(CustomFieldRelationEnum::Customer)->create($team);
    $archivedField = CustomFieldEloquentModel::factory()->linkingTo(CustomFieldRelationEnum::Customer)->archived()->create($team);
    $archivedObject = CustomObjectEloquentModel::factory()->archived()->create($team);
    $onArchivedObject = CustomFieldEloquentModel::factory()->forObject($archivedObject)->linkingTo(CustomFieldRelationEnum::Customer)->create();
    $foreign = CustomFieldEloquentModel::factory()->linkingTo(CustomFieldRelationEnum::Customer)->create();
    $secret = ProjectEloquentModel::factory()->create([...$team, 'visibility' => 'restricted']);

    TicketEloquentModel::factory()->create([...$team, 'project_id' => $secret->id, 'custom_fields' => [$live->id => $acme->id]]);
    TicketEloquentModel::factory()->create([...$team, 'custom_fields' => [$archivedField->id => $acme->id]]);
    RecordEloquentModel::factory()->of($archivedObject)->create(['custom_fields' => [$onArchivedObject->id => $acme->id]]);
    TicketEloquentModel::factory()->create(['custom_fields' => [$foreign->id => $acme->id]]);

    expect(FindLinkedRecords::to($teamId, $member->id, CustomFieldRelationEnum::Customer, $acme->id))->toBe([]);
});

it('finds what links to a ticket and to a project', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $owner->current_team_id;
    $team = ['team_id' => $teamId];
    $vehicles = CustomObjectEloquentModel::factory()->create([...$team, 'name_plural' => 'Vehicles']);
    $ticket = TicketEloquentModel::factory()->create($team);
    $project = ProjectEloquentModel::factory()->create($team);
    $lastService = CustomFieldEloquentModel::factory()->forObject($vehicles)->linkingTo(CustomFieldRelationEnum::Ticket)->create(['name' => 'Last service']);
    $programme = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Customer)->linkingTo(CustomFieldRelationEnum::Project)->create([...$team, 'name' => 'Programme']);
    RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Van', 'custom_fields' => [$lastService->id => $ticket->id]]);
    CustomerEloquentModel::factory()->create([...$team, 'name' => 'Acme', 'custom_fields' => [$programme->id => $project->id]]);

    $toTicket = FindLinkedRecords::to($teamId, $owner->id, CustomFieldRelationEnum::Ticket, $ticket->id);
    $toProject = FindLinkedRecords::to($teamId, $owner->id, CustomFieldRelationEnum::Project, $project->id);

    expect([$toTicket[0]['source'], $toTicket[0]['field'], $toTicket[0]['records'][0]['title']])->toBe(['Vehicles', 'Last service', 'Van'])
        ->and([$toProject[0]['source'], $toProject[0]['field'], $toProject[0]['records'][0]['title']])->toBe(['Customers', 'Programme', 'Acme']);
});
