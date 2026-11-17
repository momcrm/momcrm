<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomObject\CustomObjectEloquentModel;
use App\Record\RecordEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows the edit form for a contact', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $contact = ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'first_name' => 'Sam']);

    actingAs($user)
        ->get(route('contacts.edit', $contact->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('contacts/edit')->where('contact.first_name', 'Sam'));
});

it("returns not found for another team's contact", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('contacts.edit', ContactEloquentModel::factory()->create()->id))
        ->assertNotFound();
});

it('lists what links to the contact', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $team = ['team_id' => $owner->current_team_id];
    $vehicles = CustomObjectEloquentModel::factory()->create([...$team, 'name_plural' => 'Vehicles']);
    $field = CustomFieldEloquentModel::factory()->forObject($vehicles)->linkingTo(CustomFieldRelationEnum::Contact)->create(['name' => 'Linked contact']);
    $contact = ContactEloquentModel::factory()->create([...$team, 'customer_id' => CustomerEloquentModel::factory()->create($team)->id]);
    $van = RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Van', 'custom_fields' => [$field->id => $contact->id]]);

    Pest\Laravel\actingAs($owner)
        ->get(route('contacts.edit', $contact->id))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('linkedRecords', 1)
            ->where('linkedRecords.0.source', 'Vehicles')
            ->where('linkedRecords.0.field', 'Linked contact')
            ->where('linkedRecords.0.records.0', ['id' => $van->id, 'title' => 'Van', 'url' => route('records.show', [$vehicles->id, $van->id])]),
        );
});
