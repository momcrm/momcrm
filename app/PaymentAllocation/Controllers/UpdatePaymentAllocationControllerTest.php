<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\Payment\PaymentEloquentModel;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

/**
 * A paid $100.00 invoice and the $150.00 payment that paid it.
 *
 * @return array{0: UserEloquentModel, 1: InvoiceEloquentModel, 2: PaymentAllocationEloquentModel}
 */
function paidInvoiceAllocation(): array
{
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'status' => 'paid', 'paid_at' => now(), 'number' => 5, 'tax_rate_basis_points' => 0]);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $invoice->id, 'quantity' => 1, 'unit_price_in_cents' => 10000]);
    $payment = PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'number' => 2, 'amount_in_cents' => 15000]);
    $allocation = PaymentAllocationEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'payment_id' => $payment->id, 'invoice_id' => $invoice->id, 'amount_in_cents' => 10000]);

    return [$user, $invoice, $allocation];
}

it('lowers what goes to an invoice, which is then owed again', function (): void {
    [$user, $invoice, $allocation] = paidInvoiceAllocation();

    actingAs($user)
        ->put(route('payment-allocations.update', $allocation->id), ['amount' => '60'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'PAY-0002 saved.']);

    expect($allocation->fresh()->amount_in_cents)->toBe(6000)
        ->and($invoice->fresh())->status->toBe('sent')->paid_at->toBeNull();
});

it('refuses more than the invoice comes to', function (): void {
    [$user, , $allocation] = paidInvoiceAllocation();

    actingAs($user)
        ->put(route('payment-allocations.update', $allocation->id), ['amount' => '120'])
        ->assertSessionHasErrors(['amount_in_cents' => 'Only A$100.00 is owing on INV-0005.']);
});

it("returns not found for another team's allocation", function (): void {
    $allocation = PaymentAllocationEloquentModel::factory()->create();

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->put(route('payment-allocations.update', $allocation->id), ['amount' => '1'])
        ->assertNotFound();
});
