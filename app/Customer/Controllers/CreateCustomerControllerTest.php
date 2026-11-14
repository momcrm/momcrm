<?php

declare(strict_types=1);

use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows the new customer form with the statuses', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('customers.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/create')
            ->where('statuses', [['value' => 'active', 'label' => 'Active'], ['value' => 'inactive', 'label' => 'Inactive']]),
        );
});
