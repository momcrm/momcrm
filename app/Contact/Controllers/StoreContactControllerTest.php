<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
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
        ])
        ->assertRedirectToRoute('customers.show', $customer->id);

    expect(ContactEloquentModel::query()->sole())
        ->team_id->toBe($user->current_team_id)
        ->customer_id->toBe($customer->id)
        ->first_name->toBe('Sam')
        ->is_primary->toBeTrue();
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
