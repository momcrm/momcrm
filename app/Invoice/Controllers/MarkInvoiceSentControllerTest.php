<?php

declare(strict_types=1);

use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\Payment\PaymentEloquentModel;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

it('marks a draft as sent and stamps when', function (): void {
    travelTo('2026-11-14 11:00:00');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->from(route('invoices.index'))
        ->post(route('invoices.sent', $invoice->id))
        ->assertRedirectToRoute('invoices.index');

    expect($invoice->fresh())
        ->status->toBe('sent')
        ->and($invoice->fresh()->sent_at?->toDateTimeString())->toBe('2026-11-14 11:00:00');
});

it('refuses to send an invoice that is not a draft', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'status' => InvoiceStatusEnum::Paid->value]);

    actingAs($user)
        ->post(route('invoices.sent', $invoice->id))
        ->assertSessionHasErrors(['status' => 'A paid invoice cannot be marked sent.']);

    expect($invoice->fresh()->status)->toBe('paid');
});

it("pays a sent invoice from the customer's credit straight away", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'number' => 4, 'tax_rate_basis_points' => 0]);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $invoice->id, 'quantity' => 1, 'unit_price_in_cents' => 10000]);
    PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $invoice->customer_id, 'amount_in_cents' => 4000]);

    actingAs($user)
        ->post(route('invoices.sent', $invoice->id))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'INV-0004 marked as sent. A$40.00 of credit was applied to it.']);

    expect($invoice->fresh()->status)->toBe('sent')
        ->and(PaymentAllocationEloquentModel::query()->sole()->amount_in_cents)->toBe(4000);
});
