<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\Payment\PaymentEloquentModel;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;

/**
 * A sent invoice for the customer totalling $110.00 (a $100.00 line plus 10% tax).
 */
function sentInvoiceFor(CustomerEloquentModel $customer, array $attributes = []): InvoiceEloquentModel
{
    $invoice = InvoiceEloquentModel::factory()->create([
        'team_id' => $customer->team_id,
        'customer_id' => $customer->id,
        'status' => InvoiceStatusEnum::Sent->value,
        ...$attributes,
    ]);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $customer->team_id, 'invoice_id' => $invoice->id, 'quantity' => 1, 'unit_price_in_cents' => 10000]);

    return $invoice;
}

function paymentPayload(string $customerId, array $allocations, array $overrides = []): array
{
    return [
        'customer_id' => $customerId,
        'amount' => '150.00',
        'paid_on' => '2026-11-20',
        'method' => 'bank_transfer',
        'reference' => 'EFT 7781',
        'notes' => null,
        'allocations' => $allocations,
        ...$overrides,
    ];
}

it('records a payment over two invoices, paying off one and part of the other', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $first = sentInvoiceFor($customer);
    $second = sentInvoiceFor($customer);

    actingAs($user)
        ->from(route('customers.invoices', $customer->id))
        ->post(route('payments.store'), paymentPayload($customer->id, [
            ['invoice_id' => $first->id, 'amount' => '110.00'],
            ['invoice_id' => $second->id, 'amount' => '40.00'],
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirectToRoute('customers.invoices', $customer->id)
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'PAY-0001 recorded.']);

    $payment = PaymentEloquentModel::query()->sole();
    expect($payment)
        ->number->toBe(1)
        ->amount_in_cents->toBe(15000)
        ->currency->toBe('AUD')
        ->method->toBe('bank_transfer')
        ->reference->toBe('EFT 7781')
        ->and(PaymentAllocationEloquentModel::query()->where('invoice_id', $first->id)->sole()->amount_in_cents)->toBe(11000)
        ->and(PaymentAllocationEloquentModel::query()->where('invoice_id', $second->id)->sole()->amount_in_cents)->toBe(4000)
        ->and($first->fresh())->status->toBe('paid')->paid_at->not->toBeNull()
        ->and($second->fresh())->status->toBe('sent')->paid_at->toBeNull();
});

it('records the payment in the customer\'s currency', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'currency' => 'USD']);
    $invoice = sentInvoiceFor($customer, ['currency' => 'USD']);

    actingAs($user)
        ->post(route('payments.store'), paymentPayload($customer->id, [['invoice_id' => $invoice->id, 'amount' => '110']], ['amount' => '110']))
        ->assertSessionHasNoErrors();

    expect(PaymentEloquentModel::query()->sole()->currency)->toBe('USD');
});

it('refuses more for an invoice than is owing on it', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $invoice = sentInvoiceFor($customer, ['number' => 5]);
    PaymentAllocationEloquentModel::factory()->create([
        'team_id' => $user->current_team_id,
        'payment_id' => PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'amount_in_cents' => 10000]),
        'invoice_id' => $invoice->id,
        'amount_in_cents' => 10000,
    ]);

    actingAs($user)
        ->post(route('payments.store'), paymentPayload($customer->id, [['invoice_id' => $invoice->id, 'amount' => '20']], ['amount' => '20']))
        ->assertSessionHasErrors(['allocations.0.amount_in_cents' => 'Only A$10.00 is owing on INV-0005.']);

    assertDatabaseCount('payments', 1);
});

it('refuses invoices that take more than the amount received', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $invoice = sentInvoiceFor($customer);

    actingAs($user)
        ->post(route('payments.store'), paymentPayload($customer->id, [['invoice_id' => $invoice->id, 'amount' => '110']], ['amount' => '100']))
        ->assertSessionHasErrors(['amount_in_cents' => 'The invoices take A$110.00, more than the A$100.00 there is to apply.']);
});

it("keeps what isn't applied to invoices as the customer's credit", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $invoice = sentInvoiceFor($customer);

    actingAs($user)
        ->post(route('payments.store'), paymentPayload($customer->id, [['invoice_id' => $invoice->id, 'amount' => '100']], ['amount' => '120']))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'PAY-0001 recorded. A$20.00 is kept as credit.']);

    expect(PaymentEloquentModel::query()->sole()->amount_in_cents)->toBe(12000)
        ->and(PaymentAllocationEloquentModel::query()->sole()->amount_in_cents)->toBe(10000);
});

it('records a payment applied to no invoice at all', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('payments.store'), paymentPayload($customer->id, [], ['method' => 'credit']))
        ->assertSessionHasNoErrors();

    assertDatabaseCount('payments', 1);
    assertDatabaseCount('payment_allocations', 0);
});

it('refuses invoices that are not sent', function (InvoiceStatusEnum $status, string $message): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $invoice = sentInvoiceFor($customer, ['status' => $status->value, 'number' => 7]);

    actingAs($user)
        ->post(route('payments.store'), paymentPayload($customer->id, [['invoice_id' => $invoice->id, 'amount' => '110']], ['amount' => '110']))
        ->assertSessionHasErrors(['allocations.0.invoice_id' => $message]);
})->with([
    'draft' => [InvoiceStatusEnum::Draft, 'INV-0007 is draft, so it cannot be paid.'],
    'paid' => [InvoiceStatusEnum::Paid, 'INV-0007 is paid, so it cannot be paid.'],
    'void' => [InvoiceStatusEnum::Void, 'INV-0007 is void, so it cannot be paid.'],
]);

it("refuses another customer's invoice", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $other = sentInvoiceFor(CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]));

    actingAs($user)
        ->post(route('payments.store'), paymentPayload($customer->id, [['invoice_id' => $other->id, 'amount' => '110']], ['amount' => '110']))
        ->assertSessionHasErrors(['allocations.0.invoice_id' => "That invoice is not one of this customer's."]);
});

it('refuses an invoice in another currency than the customer pays in', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $invoice = sentInvoiceFor($customer, ['currency' => 'NZD', 'number' => 3]);

    actingAs($user)
        ->post(route('payments.store'), paymentPayload($customer->id, [['invoice_id' => $invoice->id, 'amount' => '110']], ['amount' => '110']))
        ->assertSessionHasErrors(['allocations.0.invoice_id' => 'INV-0003 is in NZD, not AUD.']);
});

it('asks for the customer, amount, date and method', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('payments.store'), [])
        ->assertSessionHasErrors([
            'customer_id' => 'Choose the customer who paid.',
            'amount_in_cents' => 'Enter the amount received.',
            'paid_on' => 'Enter the date it was paid.',
            'method',
        ]);
});

it("refuses another team's customer", function (): void {
    $customer = CustomerEloquentModel::factory()->create();
    $invoice = sentInvoiceFor($customer);

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('payments.store'), paymentPayload($customer->id, [['invoice_id' => $invoice->id, 'amount' => '110']], ['amount' => '110']))
        ->assertSessionHasErrors(['customer_id' => 'Choose the customer who paid.']);

    assertDatabaseCount('payments', 0);
});

it('refuses a member an access rule keeps from recording payments', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create(['onboarded_at' => now()]);
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $invoice = sentInvoiceFor($customer);
    AccessRuleEloquentModel::factory()->create([
        'team_id' => $owner->current_team_id,
        'name' => 'Only admins take payments',
        'effect' => 'deny',
        'resources' => ['payment'],
        'actions' => ['create'],
        'conditions' => [['attribute' => 'subject.team_role', 'operator' => 'equals', 'value' => 'member']],
    ]);

    actingAs($member)
        ->post(route('payments.store'), paymentPayload($customer->id, [['invoice_id' => $invoice->id, 'amount' => '110']], ['amount' => '110']))
        ->assertForbidden();
});
