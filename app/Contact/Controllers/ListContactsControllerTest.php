<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists the current team's contacts with their customer", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT']);
    ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'first_name' => 'Sam', 'last_name' => 'Lee']);
    ContactEloquentModel::factory()->create(['first_name' => 'Another team']);

    actingAs($user)
        ->get(route('contacts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('contacts/index')
            ->where('contacts.total', 1)
            ->where('contacts.data.0.full_name', 'Sam Lee')
            ->where('contacts.data.0.customer_name', 'Acme IT'),
        );
});

it('searches contacts by name or email', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'first_name' => 'Sam', 'email' => 'sam@acme.test']);
    ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'first_name' => 'Kim', 'email' => 'kim@acme.test']);

    actingAs($user)
        ->get(route('contacts.index', ['search' => 'kim']))
        ->assertInertia(fn (Assert $page) => $page->where('contacts.total', 1)->where('contacts.data.0.first_name', 'Kim'));
});
