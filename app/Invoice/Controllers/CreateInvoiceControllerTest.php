<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('opens the new-invoice dialog over the invoices', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)
        ->get(route('invoices.create'))
        ->assertRedirect(route('invoices.index', ['new' => 1]));
});

it('keeps the customer the invoice was asked for', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->get(route('invoices.create', ['customer' => $customer->id]))
        ->assertRedirect(route('invoices.index', ['new' => 1, 'customer' => $customer->id]));
});
