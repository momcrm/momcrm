<?php

declare(strict_types=1);

use App\Customer\Customer;
use App\Customer\CustomerEloquentModel;
use App\Customer\CustomerStatement;
use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\Payment\PaymentEloquentModel;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * An invoice with no tax for the customer, issued and due on the days given.
 */
function billFor(CustomerEloquentModel $customer, int $cents, string $issuedOn, string $dueOn, string $status = 'sent', int $number = 1): InvoiceEloquentModel
{
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $customer->team_id, 'customer_id' => $customer->id, 'status' => $status, 'issued_on' => $issuedOn, 'due_on' => $dueOn, 'number' => $number, 'tax_rate_basis_points' => 0]);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $customer->team_id, 'invoice_id' => $invoice->id, 'quantity' => 1, 'unit_price_in_cents' => $cents]);

    return $invoice;
}

it('carries an opening balance, then runs invoices and payments in date order to the closing balance', function (): void {
    $customer = CustomerEloquentModel::factory()->create();
    billFor($customer, 10000, '2026-09-10', '2026-09-24', number: 1);
    PaymentEloquentModel::factory()->create(['team_id' => $customer->team_id, 'customer_id' => $customer->id, 'number' => 1, 'paid_on' => '2026-09-20', 'amount_in_cents' => 4000]);
    billFor($customer, 20000, '2026-10-05', '2026-10-19', number: 2);
    PaymentEloquentModel::factory()->create(['team_id' => $customer->team_id, 'customer_id' => $customer->id, 'number' => 2, 'paid_on' => '2026-10-05', 'amount_in_cents' => 5000, 'method' => 'cash']);
    billFor($customer, 99900, '2026-10-07', '2026-10-21', status: 'draft', number: 3);
    billFor($customer, 99900, '2026-10-08', '2026-10-22', status: 'void', number: 4);
    billFor($customer, 30000, '2026-12-01', '2026-12-15', number: 5);

    $statement = CustomerStatement::for(Customer::fromEloquentModel($customer), CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-31'), CarbonImmutable::parse('2026-10-31'));

    expect($statement->openingBalanceInCents)->toBe(6000)
        ->and(array_map(fn (array $entry): array => [$entry['reference'], $entry['debit_in_cents'], $entry['credit_in_cents'], $entry['balance_in_cents']], $statement->entries))->toBe([
            ['INV-0002', 20000, 0, 26000],
            ['PAY-0002', 0, 5000, 21000],
        ])
        ->and($statement->entries[1]['description'])->toBe('Payment PAY-0002 · Cash')
        ->and($statement->closingBalanceInCents)->toBe(21000);
});

it('shows a customer in credit with a balance below zero', function (): void {
    $customer = CustomerEloquentModel::factory()->create();
    PaymentEloquentModel::factory()->create(['team_id' => $customer->team_id, 'customer_id' => $customer->id, 'paid_on' => '2026-10-02', 'amount_in_cents' => 2500]);

    $statement = CustomerStatement::for(Customer::fromEloquentModel($customer), CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-31'));

    expect($statement->closingBalanceInCents)->toBe(-2500)
        ->and($statement->creditInCents)->toBe(2500);
});

it('ages what is owing today by days past due', function (): void {
    $customer = CustomerEloquentModel::factory()->create();
    billFor($customer, 1000, '2026-10-01', '2026-11-30', number: 1);
    billFor($customer, 2000, '2026-10-01', '2026-11-10', number: 2);
    billFor($customer, 3000, '2026-08-01', '2026-09-25', number: 3);
    billFor($customer, 4000, '2026-07-01', '2026-08-25', number: 4);
    $partlyPaid = billFor($customer, 5000, '2026-06-01', '2026-07-01', number: 5);
    PaymentAllocationEloquentModel::factory()->create(['team_id' => $customer->team_id, 'invoice_id' => $partlyPaid->id, 'amount_in_cents' => 1000]);
    billFor($customer, 9000, '2026-06-01', '2026-07-01', status: 'paid', number: 6);

    $statement = CustomerStatement::for(Customer::fromEloquentModel($customer), CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-11-20'), CarbonImmutable::parse('2026-11-20'));

    expect($statement->ages)->toBe([
        'current' => 1000,
        'days_1_30' => 2000,
        'days_31_60' => 3000,
        'days_61_90' => 4000,
        'days_over_90' => 4000,
    ]);
});

it('reads the period from the query, the last three months by default', function (array $query, string $from, string $to): void {
    [$start, $end] = CustomerStatement::periodFrom(Request::create('/', 'GET', $query), CarbonImmutable::parse('2026-11-20'));

    expect([$start->toDateString(), $end->toDateString()])->toBe([$from, $to]);
})->with([
    'default' => [[], '2026-09-01', '2026-11-20'],
    'asked for' => [['from' => '2026-01-01', 'to' => '2026-03-31'], '2026-01-01', '2026-03-31'],
    'back to front' => [['from' => '2026-03-31', 'to' => '2026-01-01'], '2026-01-01', '2026-03-31'],
    'not a date' => [['from' => 'soon'], '2026-09-01', '2026-11-20'],
]);
