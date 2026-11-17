<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('saves changes to a contact', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $contact = ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'is_primary' => true]);

    actingAs($user)
        ->put(route('contacts.update', $contact->id), ['customer_id' => $customer->id, 'first_name' => 'Renamed'])
        ->assertRedirectToRoute('customers.contacts', $customer->id);

    expect($contact->fresh())
        ->first_name->toBe('Renamed')
        ->is_primary->toBeFalse();
});

it("returns not found for another team's contact", function (): void {
    $other = ContactEloquentModel::factory()->create(['first_name' => 'Theirs']);

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->put(route('contacts.update', $other->id), ['customer_id' => $other->customer_id, 'first_name' => 'Mine'])
        ->assertNotFound();

    expect($other->fresh()->first_name)->toBe('Theirs');
});

it('sets contact custom field values, leaving the ones not sent as they are', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $user->current_team_id;
    $kept = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Contact)->create(['team_id' => $teamId]);
    $set = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Contact)->select(['Gold', 'Silver'])->create(['team_id' => $teamId]);
    $contact = ContactEloquentModel::factory()->create(['team_id' => $teamId, 'customer_id' => CustomerEloquentModel::factory()->create(['team_id' => $teamId])->id, 'custom_fields' => [$kept->id => 'kept']]);

    actingAs($user)
        ->put(route('contacts.update', $contact->id), ['customer_id' => $contact->customer_id, 'first_name' => 'Sam', 'custom_fields' => [$set->id => $set->options[0]['id']]])
        ->assertSessionHasNoErrors();

    expect($contact->fresh()->custom_fields)->toEqual([$kept->id => 'kept', $set->id => $set->options[0]['id']]);
});

it('rejects a contact custom field value that is not valid, naming the field', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $user->current_team_id;
    $field = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Contact)->select(['Gold'])->create(['team_id' => $teamId, 'name' => 'Tier']);
    $ticketField = CustomFieldEloquentModel::factory()->create(['team_id' => $teamId]);
    $contact = ContactEloquentModel::factory()->create(['team_id' => $teamId, 'customer_id' => CustomerEloquentModel::factory()->create(['team_id' => $teamId])->id, 'custom_fields' => null]);

    actingAs($user)
        ->put(route('contacts.update', $contact->id), ['customer_id' => $contact->customer_id, 'first_name' => 'Sam', 'custom_fields' => [$field->id => 'nope']])
        ->assertSessionHasErrors(["custom_fields.{$field->id}" => 'The selected Tier is invalid.']);

    actingAs($user)
        ->put(route('contacts.update', $contact->id), ['customer_id' => $contact->customer_id, 'first_name' => 'Sam', 'custom_fields' => [$ticketField->id => 'SN-1']])
        ->assertSessionHasErrors('custom_fields');

    expect($contact->fresh()->custom_fields)->toBeNull();
});
