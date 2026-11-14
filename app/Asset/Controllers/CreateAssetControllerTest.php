<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows the new asset form for a customer', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT']);

    actingAs($user)
        ->get(route('assets.create', $customer->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('assets/create')->where('customer.name', 'Acme IT'));
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('assets.create', CustomerEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
