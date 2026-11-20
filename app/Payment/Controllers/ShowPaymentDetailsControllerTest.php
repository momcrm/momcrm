<?php

declare(strict_types=1);

use App\Invoice\InvoiceEloquentModel;
use App\Payment\PaymentEloquentModel;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('shows a payment with the invoices it paid', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $payment = PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'number' => 4, 'amount_in_cents' => 9000, 'method' => 'cheque']);
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $payment->customer_id, 'number' => 12]);
    PaymentAllocationEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'payment_id' => $payment->id, 'invoice_id' => $invoice->id, 'amount_in_cents' => 6000]);

    actingAs($user)
        ->getJson(route('payments.details', $payment->id))
        ->assertOk()
        ->assertJsonPath('payment.reference_number', 'PAY-0004')
        ->assertJsonPath('payment.method_label', 'Cheque')
        ->assertJsonPath('payment.allocated_in_cents', 6000)
        ->assertJsonPath('payment.unallocated_in_cents', 3000)
        ->assertJsonPath('allocations.0.invoice_reference', 'INV-0012')
        ->assertJsonPath('allocations.0.amount_in_cents', 6000)
        ->assertJsonPath('can.delete', true);
});

it("returns not found for another team's payment", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->getJson(route('payments.details', PaymentEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
