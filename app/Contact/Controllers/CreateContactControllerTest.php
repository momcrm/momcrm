<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("offers the team's customers and preselects the one asked for", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT']);
    CustomerEloquentModel::factory()->create();

    actingAs($user)
        ->get(route('contacts.create', ['customer' => $customer->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('contacts/create')
            ->where('customers', [['id' => $customer->id, 'name' => 'Acme IT']])
            ->where('customerId', $customer->id),
        );
});
