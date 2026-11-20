<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Payment\PaymentEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists only this customer's payments", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $theirs = PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);
    PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->get(route('customers.payments', $customer->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/workspace/payments')
            ->where('workspace.counts.payments', 1)
            ->where('payments.total', 1)
            ->where('payments.data.0.id', $theirs->id));
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('customers.payments', CustomerEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
