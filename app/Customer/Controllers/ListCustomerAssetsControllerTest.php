<?php

declare(strict_types=1);

use App\Asset\AssetEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists the customer's assets", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $ours = ['team_id' => $user->current_team_id, 'customer_id' => $customer->id];
    AssetEloquentModel::factory()->create([...$ours, 'name' => 'Firewall']);
    AssetEloquentModel::factory()->create(['name' => 'Another customer']);

    actingAs($user)
        ->get(route('customers.assets', $customer->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/workspace/assets')
            ->has('assets', 1)
            ->where('assets.0.name', 'Firewall'),
        );
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('customers.assets', CustomerEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
