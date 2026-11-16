<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Location\LocationEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists the customer's locations", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $ours = ['team_id' => $user->current_team_id, 'customer_id' => $customer->id];
    LocationEloquentModel::factory()->create([...$ours, 'name' => 'Head office']);
    LocationEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Elsewhere']);

    actingAs($user)
        ->get(route('customers.locations', $customer->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/workspace/locations')
            ->has('locations', 1)
            ->where('locations.0.name', 'Head office'),
        );
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('customers.locations', CustomerEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
