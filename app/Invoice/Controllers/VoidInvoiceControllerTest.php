<?php

declare(strict_types=1);

use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('voids a draft or sent invoice', function (InvoiceStatusEnum $status): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'status' => $status->value]);

    actingAs($user)
        ->from(route('invoices.index'))
        ->post(route('invoices.void', $invoice->id))
        ->assertRedirectToRoute('invoices.index');

    expect($invoice->fresh()->status)->toBe('void');
})->with([InvoiceStatusEnum::Draft, InvoiceStatusEnum::Sent]);

it('refuses to void a paid invoice', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'status' => InvoiceStatusEnum::Paid->value]);

    actingAs($user)
        ->post(route('invoices.void', $invoice->id))
        ->assertSessionHasErrors(['status' => 'A paid invoice cannot be marked void.']);
});

it('refuses to void an invoice payments have been applied to', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'status' => InvoiceStatusEnum::Sent->value, 'number' => 9]);
    PaymentAllocationEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $invoice->id]);

    actingAs($user)
        ->post(route('invoices.void', $invoice->id))
        ->assertSessionHasErrors(['status' => 'Payments have been applied to INV-0009, so it cannot be voided.']);

    expect($invoice->fresh()->status)->toBe('sent');
});
