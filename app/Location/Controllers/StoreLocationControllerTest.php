<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Location\LocationEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it("adds a location to a customer and returns to the customer's page", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('locations.store', $customer->id), ['name' => 'Head office', 'street_one' => '1 Hay St', 'suburb' => 'Perth', 'state' => 'WA', 'postcode' => '6000', 'country' => 'Australia', 'is_primary' => '1'])
        ->assertRedirectToRoute('customers.show', $customer->id);

    expect(LocationEloquentModel::query()->sole())
        ->team_id->toBe($user->current_team_id)
        ->customer_id->toBe($customer->id)
        ->name->toBe('Head office')
        ->suburb->toBe('Perth')
        ->is_primary->toBe(true);
});

it('requires the name', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('locations.store', $customer->id), [])
        ->assertSessionHasErrors(['name' => 'The name field is required.']);

    expect(LocationEloquentModel::query()->exists())->toBeFalse();
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('locations.store', CustomerEloquentModel::factory()->create()->id), ['name' => 'Head office', 'street_one' => '1 Hay St', 'suburb' => 'Perth', 'state' => 'WA', 'postcode' => '6000', 'country' => 'Australia', 'is_primary' => '1'])
        ->assertNotFound();

    expect(LocationEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});
