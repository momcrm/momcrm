<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Project\ProjectEloquentModel;
use App\TeamUser\TeamUserEloquentModel;
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

it('leaves members-only projects the person is not in out of the project picker', function (): void {
    $member = UserEloquentModel::factory()->withPersonalTeam()->create();
    $outsider = UserEloquentModel::factory()->create(['current_team_id' => $member->current_team_id]);
    TeamUserEloquentModel::query()->create(['team_id' => $member->current_team_id, 'user_id' => $outsider->id, 'role' => 'member']);
    $restricted = ProjectEloquentModel::factory()->create(['team_id' => $member->current_team_id, 'visibility' => 'restricted', 'name' => 'Secret']);
    $restricted->members()->attach($member->id);
    ProjectEloquentModel::factory()->create(['team_id' => $member->current_team_id, 'visibility' => 'open', 'name' => 'Open']);
    $names = fn ($projects): array => collect($projects)->pluck('name')->sort()->values()->all();

    actingAs($outsider)
        ->get(route('tickets.create'))
        ->assertInertia(fn (Assert $page) => $page->where('projects', fn ($projects): bool => $names($projects) === ['Open']));

    actingAs($member)
        ->get(route('tickets.create'))
        ->assertInertia(fn (Assert $page) => $page->where('projects', fn ($projects): bool => $names($projects) === ['Open', 'Secret']));
});
