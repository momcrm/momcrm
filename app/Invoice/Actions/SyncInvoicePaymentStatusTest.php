<?php

declare(strict_types=1);

use App\Invoice\Actions\SyncInvoicePaymentStatus;
use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\Team\TeamEloquentModel;

/**
 * An invoice of $100.00 with no tax.
 */
function invoiceOfOneHundred(string $teamId, InvoiceStatusEnum $status): InvoiceEloquentModel
{
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $teamId, 'status' => $status->value, 'tax_rate_basis_points' => 0]);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $teamId, 'invoice_id' => $invoice->id, 'quantity' => 1, 'unit_price_in_cents' => 10000]);

    return $invoice;
}

it('marks a sent invoice paid once payments cover it', function (): void {
    $team = TeamEloquentModel::factory()->create();
    $invoice = invoiceOfOneHundred($team->id, InvoiceStatusEnum::Sent);
    PaymentAllocationEloquentModel::factory()->count(2)->create(['team_id' => $team->id, 'invoice_id' => $invoice->id, 'amount_in_cents' => 5000]);

    SyncInvoicePaymentStatus::forInvoices([$invoice->id], $team->id);

    expect($invoice->fresh())->status->toBe('paid')->paid_at->not->toBeNull();
});

it('leaves a sent invoice that is still owed', function (): void {
    $team = TeamEloquentModel::factory()->create();
    $invoice = invoiceOfOneHundred($team->id, InvoiceStatusEnum::Sent);
    PaymentAllocationEloquentModel::factory()->create(['team_id' => $team->id, 'invoice_id' => $invoice->id, 'amount_in_cents' => 9999]);

    SyncInvoicePaymentStatus::forInvoices([$invoice->id], $team->id);

    expect($invoice->fresh()->status)->toBe('sent');
});

it('sends a paid invoice back to sent when it is owed again', function (): void {
    $team = TeamEloquentModel::factory()->create();
    $invoice = invoiceOfOneHundred($team->id, InvoiceStatusEnum::Paid);
    $invoice->update(['paid_at' => now()]);

    SyncInvoicePaymentStatus::forInvoices([$invoice->id], $team->id);

    expect($invoice->fresh())->status->toBe('sent')->paid_at->toBeNull();
});

it('leaves drafts and voided invoices alone', function (InvoiceStatusEnum $status): void {
    $team = TeamEloquentModel::factory()->create();
    $invoice = invoiceOfOneHundred($team->id, $status);

    SyncInvoicePaymentStatus::forInvoices([$invoice->id], $team->id);

    expect($invoice->fresh()->status)->toBe($status->value);
})->with([InvoiceStatusEnum::Draft, InvoiceStatusEnum::Void]);
