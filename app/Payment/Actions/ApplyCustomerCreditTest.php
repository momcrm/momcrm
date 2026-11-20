<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Invoice\Invoice;
use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\Payment\Actions\ApplyCustomerCredit;
use App\Payment\PaymentEloquentModel;
use App\PaymentAllocation\PaymentAllocationEloquentModel;

/**
 * A $100.00 invoice, with no tax, for the customer.
 */
function hundredDollarInvoice(CustomerEloquentModel $customer, InvoiceStatusEnum $status = InvoiceStatusEnum::Sent, string $currency = 'AUD'): InvoiceEloquentModel
{
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $customer->team_id, 'customer_id' => $customer->id, 'status' => $status->value, 'currency' => $currency, 'tax_rate_basis_points' => 0]);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $customer->team_id, 'invoice_id' => $invoice->id, 'quantity' => 1, 'unit_price_in_cents' => 10000]);

    return $invoice;
}

it('uses the oldest credit first until the invoice is paid', function (): void {
    $customer = CustomerEloquentModel::factory()->create();
    $invoice = hundredDollarInvoice($customer);
    $newer = PaymentEloquentModel::factory()->create(['team_id' => $customer->team_id, 'customer_id' => $customer->id, 'number' => 2, 'paid_on' => '2026-11-10', 'amount_in_cents' => 9000]);
    $older = PaymentEloquentModel::factory()->create(['team_id' => $customer->team_id, 'customer_id' => $customer->id, 'number' => 1, 'paid_on' => '2026-11-01', 'amount_in_cents' => 6000]);

    $applied = ApplyCustomerCredit::toInvoice(Invoice::fromEloquentModel($invoice));

    expect($applied)->toBe(10000)
        ->and(PaymentAllocationEloquentModel::query()->where('payment_id', $older->id)->sole()->amount_in_cents)->toBe(6000)
        ->and(PaymentAllocationEloquentModel::query()->where('payment_id', $newer->id)->sole()->amount_in_cents)->toBe(4000)
        ->and($invoice->fresh()->status)->toBe('paid');
});

it('uses only what is left of a payment, and leaves the invoice owing when credit runs out', function (): void {
    $customer = CustomerEloquentModel::factory()->create();
    $invoice = hundredDollarInvoice($customer);
    $payment = PaymentEloquentModel::factory()->create(['team_id' => $customer->team_id, 'customer_id' => $customer->id, 'amount_in_cents' => 5000]);
    PaymentAllocationEloquentModel::factory()->create(['team_id' => $customer->team_id, 'payment_id' => $payment->id, 'invoice_id' => hundredDollarInvoice($customer)->id, 'amount_in_cents' => 3000]);

    $applied = ApplyCustomerCredit::toInvoice(Invoice::fromEloquentModel($invoice));

    expect($applied)->toBe(2000)
        ->and($invoice->fresh()->status)->toBe('sent');
});

it('skips credit in another currency', function (): void {
    $customer = CustomerEloquentModel::factory()->create();
    $invoice = hundredDollarInvoice($customer, currency: 'USD');
    PaymentEloquentModel::factory()->create(['team_id' => $customer->team_id, 'customer_id' => $customer->id, 'currency' => 'AUD', 'amount_in_cents' => 5000]);

    expect(ApplyCustomerCredit::toInvoice(Invoice::fromEloquentModel($invoice)))->toBe(0);
});

it('applies nothing to an invoice that is not sent', function (InvoiceStatusEnum $status): void {
    $customer = CustomerEloquentModel::factory()->create();
    $invoice = hundredDollarInvoice($customer, $status);
    PaymentEloquentModel::factory()->create(['team_id' => $customer->team_id, 'customer_id' => $customer->id, 'amount_in_cents' => 5000]);

    expect(ApplyCustomerCredit::toInvoice(Invoice::fromEloquentModel($invoice)))->toBe(0);
    expect(PaymentAllocationEloquentModel::query()->count())->toBe(0);
})->with([InvoiceStatusEnum::Draft, InvoiceStatusEnum::Void]);
