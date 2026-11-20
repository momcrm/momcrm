<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\Payment\PaymentEloquentModel;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('takes a payment off an invoice', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'status' => 'paid', 'paid_at' => now(), 'number' => 5, 'tax_rate_basis_points' => 0]);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $invoice->id, 'quantity' => 1, 'unit_price_in_cents' => 10000]);
    $payment = PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'number' => 2, 'amount_in_cents' => 10000]);
    $allocation = PaymentAllocationEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'payment_id' => $payment->id, 'invoice_id' => $invoice->id, 'amount_in_cents' => 10000]);

    actingAs($user)
        ->delete(route('payment-allocations.destroy', $allocation->id))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'PAY-0002 taken off INV-0005. It is now credit.']);

    expect($allocation->fresh())->toBeNull()
        ->and($invoice->fresh()->status)->toBe('sent');
});

it("returns not found for another team's allocation", function (): void {
    $allocation = PaymentAllocationEloquentModel::factory()->create();

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->delete(route('payment-allocations.destroy', $allocation->id))
        ->assertNotFound();

    expect($allocation->fresh())->not->toBeNull();
});
