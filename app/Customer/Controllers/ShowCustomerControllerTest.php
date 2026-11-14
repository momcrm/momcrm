<?php

declare(strict_types=1);

use App\Asset\AssetEloquentModel;
use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Location\LocationEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows a customer with their contacts, primary first', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT']);
    ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'first_name' => 'Amy']);
    ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'first_name' => 'Zed', 'is_primary' => true]);

    actingAs($user)
        ->get(route('customers.show', $customer->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/show')
            ->where('customer.name', 'Acme IT')
            ->where('contacts.0.first_name', 'Zed')
            ->where('contacts.1.first_name', 'Amy'),
        );
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('customers.show', CustomerEloquentModel::factory()->create()->id))
        ->assertNotFound();
});

it("shows the customer's locations, assets and services", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    LocationEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'name' => 'Head office']);
    AssetEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'name' => 'Firewall']);
    ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'name' => 'Backup', 'unit_price_in_cents' => 4995]);
    AssetEloquentModel::factory()->create(['name' => 'Another customer']);

    actingAs($user)
        ->get(route('customers.show', $customer->id))
        ->assertInertia(fn (Assert $page) => $page
            ->where('locations.0.name', 'Head office')
            ->has('assets', 1)
            ->where('assets.0.name', 'Firewall')
            ->where('services.0.unit_price_in_cents', 4995),
        );
});
