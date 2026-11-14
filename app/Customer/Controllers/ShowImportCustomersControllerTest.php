<?php

declare(strict_types=1);

use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows the import page', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('customers.import'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('customers/import')->where('maxRows', 1000));
});
