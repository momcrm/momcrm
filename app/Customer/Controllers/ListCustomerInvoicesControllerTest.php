<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists the customer's invoices, optionally with one status", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $ours = ['team_id' => $user->current_team_id, 'customer_id' => $customer->id];
    InvoiceEloquentModel::factory()->create([...$ours, 'status' => 'draft']);
    $sent = InvoiceEloquentModel::factory()->create([...$ours, 'status' => 'sent']);
    InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'status' => 'sent']);

    actingAs($user)
        ->get(route('customers.invoices', $customer->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/workspace/invoices')
            ->where('invoices.total', 2),
        );

    actingAs($user)
        ->get(route('customers.invoices', ['customer' => $customer->id, 'status' => 'sent']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.status', 'sent')
            ->where('invoices.total', 1)
            ->where('invoices.data.0.id', $sent->id),
        );
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('customers.invoices', CustomerEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
