<?php

declare(strict_types=1);

use App\Charge\ChargeEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\Product\ProductEloquentModel;
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

it("lists the customer's waiting charges first, with the invoice billed ones are on, and the products to charge from", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $billed = ChargeEloquentModel::factory()->forCustomer($customer)->create(['description' => 'Billed', 'charged_on' => now()->subDay()->toDateString()]);
    ChargeEloquentModel::factory()->forCustomer($customer)->create(['description' => 'Waiting', 'charged_on' => now()->subDays(5)->toDateString()]);
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'number' => 12]);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $invoice->id, 'charge_id' => $billed->id]);
    ProductEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Laptop setup']);

    actingAs($user)
        ->get(route('customers.invoices', $customer->id))
        ->assertInertia(fn (Assert $page) => $page
            ->where('charges.0.description', 'Waiting')
            ->where('charges.0.invoice', null)
            ->where('charges.1.description', 'Billed')
            ->where('charges.1.invoice', ['id' => $invoice->id, 'reference' => 'INV-0012'])
            ->where('products.0.name', 'Laptop setup')
            ->where('canCharge', true));
});
