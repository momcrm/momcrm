<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
use App\Location\LocationEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('saves changes to a location', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $location = LocationEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);

    actingAs($user)
        ->put(route('locations.update', $location->id), ['name' => 'Head office', 'street_one' => '1 Hay St', 'suburb' => 'Perth', 'state' => 'WA', 'postcode' => '6000', 'country' => 'Australia', 'is_primary' => '1'])
        ->assertRedirectToRoute('customers.locations', $customer->id);

    expect($location->fresh())
        ->name->toBe('Head office')
        ->suburb->toBe('Perth')
        ->is_primary->toBe(true);
});

it("returns not found for another team's location", function (): void {
    $other = LocationEloquentModel::factory()->create();

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->put(route('locations.update', $other->id), ['name' => 'Head office', 'street_one' => '1 Hay St', 'suburb' => 'Perth', 'state' => 'WA', 'postcode' => '6000', 'country' => 'Australia', 'is_primary' => '1'])
        ->assertNotFound();
});

it('changes a location custom field value, keeping the ones not sent', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $kept = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Location)->create($team);
    $set = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Location)->create($team);
    $location = LocationEloquentModel::factory()->create([...$team, 'customer_id' => CustomerEloquentModel::factory()->create($team)->id, 'custom_fields' => [$kept->id => 'kept']]);

    Pest\Laravel\actingAs($user)
        ->put(route('locations.update', $location->id), ['name' => 'Head office', 'custom_fields' => [$set->id => 'new']])
        ->assertSessionHasNoErrors();

    expect($location->fresh()->custom_fields)->toEqual([$kept->id => 'kept', $set->id => 'new']);
});
