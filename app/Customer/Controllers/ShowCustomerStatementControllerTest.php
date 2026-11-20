<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Payment\PaymentEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("shows the customer's statement for the period asked for, with who it can be sent to", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'number' => 1, 'paid_on' => '2026-10-02', 'amount_in_cents' => 2500]);
    $contact = ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'email' => 'accounts@acme.example']);
    ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'email' => null]);

    actingAs($user)
        ->get(route('customers.statement', [$customer->id, 'from' => '2026-10-01', 'to' => '2026-10-31']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/workspace/statement')
            ->where('statement.from', '2026-10-01')
            ->where('statement.to', '2026-10-31')
            ->where('statement.entries.0.reference', 'PAY-0001')
            ->where('statement.closing_balance_in_cents', -2500)
            ->has('contacts', 1)
            ->where('contacts.0.id', $contact->id)
            ->where('canSend', true));
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('customers.statement', CustomerEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
