<?php

declare(strict_types=1);

use App\Asset\AssetEloquentModel;
use App\Choice\ChoiceEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Location\LocationEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it("adds a asset to a customer and returns to the customer's page", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('assets.store', $customer->id), ['name' => 'Reception PC', 'serial_number' => 'SN-1', 'status' => 'active'])
        ->assertRedirectToRoute('customers.assets', $customer->id);

    expect(AssetEloquentModel::query()->sole())
        ->team_id->toBe($user->current_team_id)
        ->customer_id->toBe($customer->id)
        ->name->toBe('Reception PC')
        ->serial_number->toBe('SN-1')
        ->status->toBe('active');
});

it('requires the name', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('assets.store', $customer->id), [])
        ->assertSessionHasErrors(['name' => 'The name field is required.']);

    expect(AssetEloquentModel::query()->exists())->toBeFalse();
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('assets.store', CustomerEloquentModel::factory()->create()->id), ['name' => 'Reception PC', 'serial_number' => 'SN-1', 'status' => 'active'])
        ->assertNotFound();

    expect(AssetEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});

it('rejects a location that belongs to another customer', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $otherLocation = LocationEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('assets.store', $customer->id), ['name' => 'PC', 'status' => 'active', 'location_id' => $otherLocation->id])
        ->assertSessionHasErrors(['location_id' => 'The selected location id is invalid.']);
});

it('adds an asset with labels', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $label = ChoiceEloquentModel::factory()->label()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('assets.store', $customer->id), ['name' => 'Reception PC', 'status' => 'active', 'label_ids' => [$label->id]])
        ->assertSessionHasNoErrors();

    expect(AssetEloquentModel::query()->sole()->labels->pluck('id')->all())->toBe([$label->id]);
});
