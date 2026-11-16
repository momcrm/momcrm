<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Location\LocationEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('saves changes to a customer', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'number' => 4]);

    actingAs($user)
        ->put(route('customers.update', $customer->id), ['name' => 'Renamed', 'status' => 'inactive', 'number' => 99])
        ->assertRedirectToRoute('customers.edit', $customer->id);

    expect($customer->fresh())
        ->name->toBe('Renamed')
        ->status->toBe('inactive')
        ->number->toBe(4);
});

it("returns not found for another team's customer", function (): void {
    $other = CustomerEloquentModel::factory()->create(['name' => 'Theirs']);

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->put(route('customers.update', $other->id), ['name' => 'Mine', 'status' => 'active'])
        ->assertNotFound();

    expect($other->fresh()->name)->toBe('Theirs');
});

it('lets a customer keep its own prefix', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'prefix' => 'ACME']);

    actingAs($user)
        ->put(route('customers.update', $customer->id), ['name' => 'Acme renamed', 'status' => 'active', 'prefix' => 'acme', 'billing_type' => 'excluded'])
        ->assertSessionHasNoErrors();

    expect($customer->refresh())
        ->name->toBe('Acme renamed')
        ->prefix->toBe('ACME')
        ->billing_type->toBe('excluded');
});

it('makes the chosen contact and location the main ones, and the others not', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $old = ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'is_primary' => true]);
    $new = ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);
    $site = LocationEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);

    actingAs($user)
        ->put(route('customers.update', $customer->id), [
            'name' => $customer->name,
            'status' => 'active',
            'main_contact_id' => $new->id,
            'main_location_id' => $site->id,
        ])
        ->assertSessionHasNoErrors();

    expect($old->refresh()->is_primary)->toBeFalse()
        ->and($new->refresh()->is_primary)->toBeTrue()
        ->and($site->refresh()->is_primary)->toBeTrue();
});

it('clears the main contact when none is chosen', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $contact = ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'is_primary' => true]);

    actingAs($user)
        ->put(route('customers.update', $customer->id), ['name' => $customer->name, 'status' => 'active', 'main_contact_id' => '', 'main_location_id' => ''])
        ->assertSessionHasNoErrors();

    expect($contact->refresh()->is_primary)->toBeFalse();
});

it("rejects another customer's contact as the main contact", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $elsewhere = ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->put(route('customers.update', $customer->id), ['name' => $customer->name, 'status' => 'active', 'main_contact_id' => $elsewhere->id, 'main_location_id' => ''])
        ->assertSessionHasErrors(['main_contact_id' => "Choose one of this customer's contacts."]);

    expect($elsewhere->refresh()->is_primary)->toBeFalse();
});
