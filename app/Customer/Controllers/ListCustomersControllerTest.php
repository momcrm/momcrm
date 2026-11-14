<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it("lists the current team's customers by name with their contact counts", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $zeta = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Zeta Pty Ltd']);
    CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT']);
    CustomerEloquentModel::factory()->create(['name' => 'Another team']);
    ContactEloquentModel::factory()->count(2)->create(['team_id' => $user->current_team_id, 'customer_id' => $zeta->id]);

    actingAs($user)
        ->get(route('customers.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/index')
            ->where('customers.total', 2)
            ->where('customers.data.0.name', 'Acme IT')
            ->where('customers.data.1.name', 'Zeta Pty Ltd')
            ->where('customers.data.1.contacts_count', 2),
        );
});

it('searches customers by name or email', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT', 'email' => 'hi@acme.test']);
    CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Zeta', 'email' => 'ops@zeta.test']);

    actingAs($user)
        ->get(route('customers.index', ['search' => 'zeta.test']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('customers.total', 1)
            ->where('customers.data.0.name', 'Zeta')
            ->where('filters.search', 'zeta.test'),
        );
});

it('requires finishing onboarding', function (): void {
    actingAs(UserEloquentModel::factory()->notOnboarded()->withPersonalTeam()->create())
        ->get(route('customers.index'))
        ->assertRedirectToRoute('onboarding.about');
});

it('requires authentication', function (): void {
    get(route('customers.index'))->assertRedirectToRoute('login');
});
