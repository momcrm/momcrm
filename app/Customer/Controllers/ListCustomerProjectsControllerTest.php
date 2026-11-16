<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Project\ProjectEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists the customer's projects", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $ours = ['team_id' => $user->current_team_id, 'customer_id' => $customer->id];
    ProjectEloquentModel::factory()->create([...$ours, 'name' => 'Office move']);
    ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Someone else']);

    actingAs($user)
        ->get(route('customers.projects', $customer->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/workspace/projects')
            ->has('projects', 1)
            ->where('projects.0.name', 'Office move'),
        );
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('customers.projects', CustomerEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
