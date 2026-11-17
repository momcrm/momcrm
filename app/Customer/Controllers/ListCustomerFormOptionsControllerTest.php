<?php

declare(strict_types=1);

use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

it('returns the statuses and billing types for the new-customer dialog', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->getJson(route('customers.form-options'))
        ->assertOk()
        ->assertJsonPath('statuses', [['value' => 'active', 'label' => 'Active'], ['value' => 'inactive', 'label' => 'Inactive']])
        ->assertJsonPath('billingTypes.1', ['value' => 'manual', 'label' => 'Manual']);
});

it('needs a signed-in person', function (): void {
    getJson(route('customers.form-options'))->assertUnauthorized();
});
