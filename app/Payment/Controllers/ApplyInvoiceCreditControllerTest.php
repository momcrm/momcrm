<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\Payment\PaymentEloquentModel;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it("pays an invoice from the customer's credit", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'status' => 'sent', 'number' => 6, 'tax_rate_basis_points' => 0]);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $invoice->id, 'quantity' => 1, 'unit_price_in_cents' => 10000]);
    PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'amount_in_cents' => 2500]);

    actingAs($user)
        ->post(route('invoices.apply-credit', $invoice->id))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'A$25.00 of credit applied to INV-0006.']);

    expect(PaymentAllocationEloquentModel::query()->sole()->amount_in_cents)->toBe(2500);
});

it('says so when there is no credit to apply', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'status' => 'sent']);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $invoice->id]);

    actingAs($user)
        ->post(route('invoices.apply-credit', $invoice->id))
        ->assertSessionHasErrors(['invoice' => 'This customer has no credit to apply.']);
});

it('only pays sent invoices', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('invoices.apply-credit', $invoice->id))
        ->assertSessionHasErrors(['invoice' => 'Only a sent invoice can be paid.']);
});

it("returns not found for another team's invoice", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('invoices.apply-credit', InvoiceEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
