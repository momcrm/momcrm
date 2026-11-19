<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Rate\RateEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists the customer's services with what they cost, leaving archived ones out unless asked", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $ours = ['team_id' => $user->current_team_id, 'customer_id' => $customer->id];
    $rate = RateEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'price_in_cents' => 4995]);
    ServiceEloquentModel::factory()->create([...$ours, 'name' => 'Backup', 'rate_id' => $rate->id, 'quantity' => 2]);
    ServiceEloquentModel::factory()->archived()->create([...$ours, 'name' => 'Old backup']);
    ServiceEloquentModel::factory()->create(['name' => 'Another customer']);

    actingAs($user)
        ->get(route('customers.services', $customer->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/workspace/services')
            ->has('services', 1)
            ->where('services.0.lifecycle', 'active')
            ->where('services.0.unit_price_in_cents', 4995)
            ->where('services.0.rental_in_cents', 9990)
            ->where('archivedCount', 1)
            ->where('showsArchived', false)
            ->where('workspace.counts.services', 1),
        );

    actingAs($user)
        ->get(route('customers.services', [$customer->id, 'archived' => 1]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('services', 2)
            ->where('services.1.lifecycle', 'archived'),
        );
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('customers.services', CustomerEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
