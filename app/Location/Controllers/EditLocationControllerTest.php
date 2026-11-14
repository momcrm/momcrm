<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Location\LocationEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows the edit form for a location', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $location = LocationEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);

    actingAs($user)
        ->get(route('locations.edit', $location->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('locations/edit')->where('location.id', $location->id)->where('customer.id', $customer->id));
});

it("returns not found for another team's location", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('locations.edit', LocationEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
