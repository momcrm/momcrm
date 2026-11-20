<?php

declare(strict_types=1);

use App\Charge\ChargeEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\Product\ProductEloquentModel;
use App\Rate\RateEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

it("sends a page of the Billing tab the customer's billable services, the products and the lines waiting", function (): void {
    travelTo('2026-11-20 09:00:00');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $rate = RateEloquentModel::factory()->priced(['AUD' => 3000])->create(['team_id' => $user->current_team_id]);
    ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'rate_id' => $rate->id, 'name' => 'Backup']);
    ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'rate_id' => null, 'name' => 'Not on a rate']);
    ServiceEloquentModel::factory()->inactive()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'rate_id' => $rate->id]);
    ProductEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Laptop setup']);
    ChargeEloquentModel::factory()->forCustomer($customer)->create(['description' => 'Waiting']);
    $billed = ChargeEloquentModel::factory()->forCustomer($customer)->create(['description' => 'Billed']);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id])->id, 'charge_id' => $billed->id]);

    actingAs($user)
        ->get(route('customers.billing', ['customer' => $customer->id, 'page' => 'combine']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/workspace/billing')
            ->where('page', 'combine')
            ->has('pages', 4)
            ->where('month', '2026-11')
            ->has('services', 1)
            ->where('services.0.name', 'Backup')
            ->where('products.0.name', 'Laptop setup')
            ->has('waiting', 1)
            ->where('waiting.0.description', 'Waiting')
            ->where('workspace.counts.waiting_lines', 1)
            ->where('can', ['invoice' => true, 'charge' => true]));

    actingAs($user)
        ->get(route('customers.billing', $customer->id))
        ->assertInertia(fn (Assert $page) => $page->where('page', 'multiple'));
});

it('is not found for a page Amphora has no billing for', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)->get("/customers/{$customer->id}/billing/finance")->assertNotFound();
});
