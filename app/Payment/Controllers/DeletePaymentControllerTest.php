<?php

declare(strict_types=1);

use App\BankAccount\BankAccountEloquentModel;
use App\BankTransaction\BankTransactionEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\Payment\PaymentEloquentModel;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelMissing;

it('deletes a payment and sends the invoice it paid back to owing', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $payment = PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'number' => 7, 'amount_in_cents' => 10000]);
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $payment->customer_id, 'status' => InvoiceStatusEnum::Paid->value, 'paid_at' => now(), 'tax_rate_basis_points' => 0]);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $invoice->id, 'quantity' => 1, 'unit_price_in_cents' => 10000]);
    $allocation = PaymentAllocationEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'payment_id' => $payment->id, 'invoice_id' => $invoice->id, 'amount_in_cents' => 10000]);

    actingAs($user)
        ->from(route('payments.index', ['method' => 'cash', 'payment' => $payment->id]))
        ->delete(route('payments.destroy', $payment->id))
        ->assertRedirect(route('payments.index', ['method' => 'cash']))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'PAY-0007 deleted.']);

    assertModelMissing($payment);
    assertModelMissing($allocation);
    expect($invoice->fresh())->status->toBe('sent')->paid_at->toBeNull();
});

it('leaves deleting payments to owners and admins by default', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $payment = PaymentEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)->delete(route('payments.destroy', $payment->id))->assertForbidden();

    expect($payment->fresh())->not->toBeNull();
});

it("returns not found for another team's payment", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->delete(route('payments.destroy', PaymentEloquentModel::factory()->create()->id))
        ->assertNotFound();
});

it('returns the bank feed line it was recorded from to pending', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $payment = PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $line = BankTransactionEloquentModel::factory()
        ->forAccount(BankAccountEloquentModel::factory()->create(['team_id' => $user->current_team_id]))
        ->create(['payment_id' => $payment->id, 'customer_id' => $payment->customer_id]);

    actingAs($user)->delete(route('payments.destroy', $payment->id))->assertSessionHasNoErrors();

    expect($line->fresh())->payment_id->toBeNull()->customer_id->toBe($payment->customer_id);
});
