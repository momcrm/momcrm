<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
use App\Invoice\InvoiceEloquentModel;
use App\Location\LocationEloquentModel;
use App\Rate\RateEloquentModel;
use App\Service\ServiceEloquentModel;
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

it('moves a customer to another currency their services are priced in', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $customer = CustomerEloquentModel::factory()->create($team);
    $rate = RateEloquentModel::factory()->priced(['AUD' => 9500, 'USD' => 6200])->create($team);
    ServiceEloquentModel::factory()->create([...$team, 'customer_id' => $customer->id, 'rate_id' => $rate->id]);
    InvoiceEloquentModel::factory()->create([...$team, 'customer_id' => $customer->id, 'status' => 'void']);

    actingAs($user)
        ->put(route('customers.update', $customer->id), ['name' => $customer->name, 'status' => 'active', 'currency' => 'USD'])
        ->assertSessionHasNoErrors();

    expect($customer->fresh()->currency)->toBe('USD');
});

it('keeps a customer in their currency once invoiced, or while a service has no price in the new one', function (Closure $setUp, string $message): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $setUp($customer);

    actingAs($user)
        ->put(route('customers.update', $customer->id), ['name' => $customer->name, 'status' => 'active', 'currency' => 'USD'])
        ->assertSessionHasErrors(['currency' => $message]);

    expect($customer->fresh()->currency)->toBe('AUD');
})->with([
    'invoiced' => [fn (CustomerEloquentModel $customer) => InvoiceEloquentModel::factory()->create(['team_id' => $customer->team_id, 'customer_id' => $customer->id]), 'This customer has been invoiced in AUD, so they stay in it.'],
    'a service unpriced in USD' => [fn (CustomerEloquentModel $customer) => ServiceEloquentModel::factory()->create([
        'team_id' => $customer->team_id,
        'customer_id' => $customer->id,
        'rate_id' => RateEloquentModel::factory()->priced(['AUD' => 9500])->create(['team_id' => $customer->team_id, 'name' => 'Support'])->id,
    ]), 'Give Support a price in USD first.'],
]);

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

it('sets customer custom field values, leaving the ones not sent as they are', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $user->current_team_id;
    $kept = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Customer)->create(['team_id' => $teamId]);
    $set = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Customer)->select(['Gold', 'Silver'])->create(['team_id' => $teamId]);
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $teamId, 'name' => 'Acme', 'custom_fields' => [$kept->id => 'kept']]);

    actingAs($user)
        ->put(route('customers.update', $customer->id), ['name' => 'Acme', 'status' => 'active', 'custom_fields' => [$set->id => $set->options[0]['id']]])
        ->assertSessionHasNoErrors();

    expect($customer->fresh()->custom_fields)->toEqual([$kept->id => 'kept', $set->id => $set->options[0]['id']]);
});

it('rejects a customer custom field value that is not valid, naming the field', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $user->current_team_id;
    $field = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Customer)->select(['Gold'])->create(['team_id' => $teamId, 'name' => 'Tier']);
    $ticketField = CustomFieldEloquentModel::factory()->create(['team_id' => $teamId]);
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $teamId, 'name' => 'Acme', 'custom_fields' => null]);

    actingAs($user)
        ->put(route('customers.update', $customer->id), ['name' => 'Acme', 'status' => 'active', 'custom_fields' => [$field->id => 'nope']])
        ->assertSessionHasErrors(["custom_fields.{$field->id}" => 'The selected Tier is invalid.']);

    actingAs($user)
        ->put(route('customers.update', $customer->id), ['name' => 'Acme', 'status' => 'active', 'custom_fields' => [$ticketField->id => 'SN-1']])
        ->assertSessionHasErrors('custom_fields');

    expect($customer->fresh()->custom_fields)->toBeNull();
});
