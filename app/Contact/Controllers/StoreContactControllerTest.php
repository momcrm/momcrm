<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it("adds a contact to a customer and returns to the customer's page", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('contacts.store'), [
            'customer_id' => $customer->id,
            'first_name' => 'Sam',
            'last_name' => 'Lee',
            'email' => 'sam@acme.test',
            'position' => 'Office manager',
            'is_primary' => '1',
            'receives_invoices' => '1',
        ])
        ->assertRedirectToRoute('customers.contacts', $customer->id);

    expect(ContactEloquentModel::query()->sole())
        ->team_id->toBe($user->current_team_id)
        ->customer_id->toBe($customer->id)
        ->first_name->toBe('Sam')
        ->is_primary->toBeTrue()
        ->receives_invoices->toBeTrue();
});

it('keeps one primary contact per customer', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $previous = ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'is_primary' => true]);

    actingAs($user)->post(route('contacts.store'), ['customer_id' => $customer->id, 'first_name' => 'New', 'is_primary' => '1']);

    expect($previous->fresh()->is_primary)->toBeFalse();
});

it("rejects another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('contacts.store'), ['customer_id' => CustomerEloquentModel::factory()->create()->id, 'first_name' => 'Sam'])
        ->assertSessionHasErrors(['customer_id' => 'Choose the customer this contact works for.']);

    expect(ContactEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});

it('requires a first name', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('contacts.store'), ['customer_id' => $customer->id])
        ->assertSessionHasErrors(['first_name' => 'The first name field is required.']);
});

it('adds a contact with its custom field values', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $user->current_team_id;
    $field = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Contact)->create(['team_id' => $teamId]);
    $blank = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Contact)->create(['team_id' => $teamId]);
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $teamId]);

    actingAs($user)
        ->post(route('contacts.store'), ['customer_id' => $customer->id, 'first_name' => 'Sam', 'custom_fields' => [$field->id => 'North', $blank->id => '']])
        ->assertSessionHasNoErrors();

    expect(ContactEloquentModel::query()->where('first_name', 'Sam')->sole()->custom_fields)->toBe([$field->id => 'North']);
});
