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
 * A sent $100.00 invoice, with no tax, for the customer.
 */
function owingHundred(CustomerEloquentModel $customer, int $number): InvoiceEloquentModel
{
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $customer->team_id, 'customer_id' => $customer->id, 'status' => 'sent', 'number' => $number, 'tax_rate_basis_points' => 0]);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $customer->team_id, 'invoice_id' => $invoice->id, 'quantity' => 1, 'unit_price_in_cents' => 10000]);

    return $invoice;
}

it('applies what is left of a payment, adding to an invoice it already paid part of', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $first = owingHundred($customer, 1);
    $second = owingHundred($customer, 2);
    $payment = PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'number' => 3, 'amount_in_cents' => 15000]);
    PaymentAllocationEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'payment_id' => $payment->id, 'invoice_id' => $first->id, 'amount_in_cents' => 6000]);

    actingAs($user)
        ->post(route('payments.allocations.store', $payment->id), ['allocations' => [
            ['invoice_id' => $first->id, 'amount' => '40'],
            ['invoice_id' => $second->id, 'amount' => '50'],
        ]])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'PAY-0003 applied.']);

    expect(PaymentAllocationEloquentModel::query()->where('invoice_id', $first->id)->sole()->amount_in_cents)->toBe(10000)
        ->and(PaymentAllocationEloquentModel::query()->where('invoice_id', $second->id)->sole()->amount_in_cents)->toBe(5000)
        ->and($first->fresh()->status)->toBe('paid');
});

it('refuses more than is left of the payment', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $invoice = owingHundred($customer, 1);
    $payment = PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'amount_in_cents' => 3000]);

    actingAs($user)
        ->post(route('payments.allocations.store', $payment->id), ['allocations' => [['invoice_id' => $invoice->id, 'amount' => '40']]])
        ->assertSessionHasErrors(['allocations' => 'The invoices take A$40.00, more than the A$30.00 there is to apply.']);
});

it("returns not found for another team's payment", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('payments.allocations.store', PaymentEloquentModel::factory()->create()->id), ['allocations' => []])
        ->assertNotFound();
});
