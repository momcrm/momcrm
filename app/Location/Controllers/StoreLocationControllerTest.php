<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldTypeEnum;
use App\Location\LocationEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it("adds a location to a customer and returns to the customer's page", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('locations.store', $customer->id), ['name' => 'Head office', 'street_one' => '1 Hay St', 'suburb' => 'Perth', 'state' => 'WA', 'postcode' => '6000', 'country' => 'Australia', 'is_primary' => '1'])
        ->assertRedirectToRoute('customers.locations', $customer->id);

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

it('adds a location with its custom field values, rejecting one of the wrong type', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $customer = CustomerEloquentModel::factory()->create($team);
    $text = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Location)->create($team);
    $number = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Location)->ofType(CustomFieldTypeEnum::Number)->create([...$team, 'name' => 'Count']);

    Pest\Laravel\actingAs($user)
        ->post(route('locations.store', $customer->id), ['name' => 'Head office', 'custom_fields' => [$number->id => 'many']])
        ->assertSessionHasErrors(["custom_fields.{$number->id}" => 'The Count field must be a number.']);

    Pest\Laravel\actingAs($user)
        ->post(route('locations.store', $customer->id), ['name' => 'Head office', 'custom_fields' => [$text->id => 'North', $number->id => '2']])
        ->assertSessionHasNoErrors();

    expect(LocationEloquentModel::query()->withoutGlobalScopes()->sole()->custom_fields)->toEqual([$text->id => 'North', $number->id => 2]);
});
