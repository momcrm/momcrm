<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists the customer's contacts, main contact first", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $ours = ['team_id' => $user->current_team_id, 'customer_id' => $customer->id];
    ContactEloquentModel::factory()->create([...$ours, 'first_name' => 'Amy']);
    ContactEloquentModel::factory()->create([...$ours, 'first_name' => 'Zed', 'is_primary' => true]);
    ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'first_name' => 'Elsewhere']);

    actingAs($user)
        ->get(route('customers.contacts', $customer->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/workspace/contacts')
            ->has('contacts', 2)
            ->where('contacts.0.first_name', 'Zed')
            ->where('contacts.1.first_name', 'Amy'),
        );
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('customers.contacts', CustomerEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
