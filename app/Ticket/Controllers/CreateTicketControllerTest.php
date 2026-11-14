<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("offers the team's customers, contacts and members", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['name' => 'Jane Smith']);
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT']);
    ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'first_name' => 'Sam', 'last_name' => 'Lee']);
    CustomerEloquentModel::factory()->create();

    actingAs($user)
        ->get(route('tickets.create', ['customer' => $customer->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tickets/create')
            ->where('customerId', $customer->id)
            ->where('customers', [['id' => $customer->id, 'name' => 'Acme IT']])
            ->where('contacts.0.name', 'Sam Lee')
            ->where('contacts.0.customer_id', $customer->id)
            ->where('members', [['id' => $user->id, 'name' => 'Jane Smith']]),
        );
});
