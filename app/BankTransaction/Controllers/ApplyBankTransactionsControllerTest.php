<?php

declare(strict_types=1);

use App\BankAccount\BankAccountEloquentModel;
use App\BankTransaction\BankTransactionEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\Payment\PaymentEloquentModel;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

function owing_invoice(CustomerEloquentModel $customer, string $dueOn, int $cents): InvoiceEloquentModel
{
    $invoice = InvoiceEloquentModel::factory()->create([
        'team_id' => $customer->team_id,
        'customer_id' => $customer->id,
        'status' => InvoiceStatusEnum::Sent->value,
        'due_on' => $dueOn,
        'tax_rate_basis_points' => 0,
    ]);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $customer->team_id, 'invoice_id' => $invoice->id, 'quantity' => 1, 'unit_price_in_cents' => $cents]);

    return $invoice;
}

it('records a payment from each line, paying the oldest invoices first and keeping the rest as credit', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $newer = owing_invoice($customer, '2025-12-01', 10000);
    $older = owing_invoice($customer, '2025-11-01', 10000);
    $line = BankTransactionEloquentModel::factory()
        ->forAccount(BankAccountEloquentModel::factory()->create(['team_id' => $user->current_team_id]))
        ->create(['amount_in_cents' => 25000, 'customer_id' => $customer->id, 'matched_by' => 'manual', 'posted_on' => '2025-11-13', 'description' => 'HARBOUR DENTAL']);

    actingAs($user)
        ->post(route('bank-feeds.apply'), ['bank_transaction_ids' => [$line->id]])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '1 payment recorded.']);

    $payment = PaymentEloquentModel::query()->sole();

    expect($payment)
        ->amount_in_cents->toBe(25000)
        ->paid_on->toDateString()->toBe('2025-11-13')
        ->method->toBe('bank_transfer')
        ->notes->toBe('HARBOUR DENTAL')
        ->and($line->fresh()->payment_id)->toBe($payment->id)
        ->and(PaymentAllocationEloquentModel::query()->pluck('amount_in_cents', 'invoice_id')->all())->toEqual([$older->id => 10000, $newer->id => 10000])
        ->and($older->fresh()->status)->toBe('paid');
});

it('refuses lines without a customer', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $line = BankTransactionEloquentModel::factory()->forAccount(BankAccountEloquentModel::factory()->create(['team_id' => $user->current_team_id]))->create();

    actingAs($user)
        ->post(route('bank-feeds.apply'), ['bank_transaction_ids' => [$line->id]])
        ->assertSessionHasErrors(['bank_transaction_ids' => 'Pick pending lines that have a customer.']);

    expect(PaymentEloquentModel::query()->count())->toBe(0);
});
