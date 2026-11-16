<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists the customer's services", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $ours = ['team_id' => $user->current_team_id, 'customer_id' => $customer->id];
    ServiceEloquentModel::factory()->create([...$ours, 'name' => 'Backup', 'unit_price_in_cents' => 4995]);
    ServiceEloquentModel::factory()->create(['name' => 'Another customer']);

    actingAs($user)
        ->get(route('customers.services', $customer->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/workspace/services')
            ->has('services', 1)
            ->where('services.0.unit_price_in_cents', 4995),
        );
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('customers.services', CustomerEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
