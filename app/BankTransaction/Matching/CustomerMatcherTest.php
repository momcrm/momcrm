<?php

declare(strict_types=1);

use App\BankAccount\BankAccount;
use App\BankAccount\BankAccountEloquentModel;
use App\BankTransaction\Matching\CustomerMatcher;
use App\Customer\CustomerEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\Team\TeamEloquentModel;

/**
 * Matches a description for a fresh team's AUD account, after $setUp has
 * added the team's customers and invoices.
 */
function match_customer(string $description, Closure $setUp): ?string
{
    $team = TeamEloquentModel::factory()->create(['name' => 'Cebugle', 'billing_name' => 'Cebugle IT Pty Ltd']);
    $setUp($team->id);
    $account = BankAccountEloquentModel::factory()->create(['team_id' => $team->id]);

    return CustomerMatcher::forAccount(BankAccount::fromEloquentModel($account))->match($description)?->customerId;
}

function matcher_customer(string $teamId, string $name, array $attributes = []): string
{
    return CustomerEloquentModel::factory()->create(['team_id' => $teamId, 'name' => $name, ...$attributes])->id;
}

function matcher_invoice(string $teamId, string $customerId, int $number, string $prefix = 'INV-'): void
{
    InvoiceEloquentModel::factory()->create(['team_id' => $teamId, 'customer_id' => $customerId, 'number' => $number, 'prefix' => $prefix, 'status' => InvoiceStatusEnum::Sent->value]);
}

it('finds the customer billed on an invoice quoted in the description', function (string $description): void {
    $expected = null;

    $found = match_customer($description, function (string $teamId) use (&$expected): void {
        $expected = matcher_customer($teamId, 'Harbour Dental');
        matcher_invoice($teamId, $expected, 42);
        matcher_customer($teamId, 'Coastal Logistics');
    });

    expect($found)->toBe($expected);
})->with([
    'with its prefix' => ['Direct Credit 062000 PAYMENT INV-0042'],
    'run together' => ['TRANSFER FROM J CITIZEN inv42'],
    'zero-padded, four digits' => ['Fast Transfer From J Citizen 0042'],
]);

it('finds the customer by their customer number or code', function (string $description): void {
    $expected = null;

    $found = match_customer($description, function (string $teamId) use (&$expected): void {
        $expected = matcher_customer($teamId, 'Harbour Dental', ['number' => 4815, 'prefix' => 'HDC']);
        matcher_customer($teamId, 'Coastal Logistics', ['number' => 16]);
    });

    expect($found)->toBe($expected);
})->with([
    'number' => ['Direct Credit 062000 ACCT 4815'],
    'code' => ['Direct Credit 062000 HDC NOVEMBER'],
]);

it('settles nothing when the numbers point at different customers', function (): void {
    expect(match_customer('PAYMENT INV-0042 INV-0043', function (string $teamId): void {
        matcher_invoice($teamId, matcher_customer($teamId, 'Harbour Dental'), 42);
        matcher_invoice($teamId, matcher_customer($teamId, 'Coastal Logistics'), 43);
    }))->toBeNull();
});

it('reads neither a year nor the BSB after Direct Credit as a number', function (string $description): void {
    expect(match_customer($description, function (string $teamId): void {
        matcher_customer($teamId, 'Harbour Dental', ['number' => 2025]);
        matcher_invoice($teamId, matcher_customer($teamId, 'Coastal Logistics'), 62000, '');
    }))->toBeNull();
})->with([
    'year' => ['Fast Transfer From J Citizen 2025'],
    'bsb' => ['Direct Credit 062000 UNRELATED PAYER PTY LTD'],
]);

it('matches customer names the way Amphora does', function (string $description, array $names, ?string $expected): void {
    $ids = [];

    $found = match_customer($description, function (string $teamId) use ($names, &$ids): void {
        foreach ($names as $name) {
            $ids[$name] = matcher_customer($teamId, $name);
        }
    });

    expect($found)->toBe($expected === null ? null : $ids[$expected]);
})->with([
    'a name the bank cut short' => ['Direct Credit 529470 WRENFIELD INSURANC 431998', ['Wrenfield Insurance Brokers'], 'Wrenfield Insurance Brokers'],
    'the customer matching most words wins' => ['Direct Credit 421520 Marnock Exploratio Marnock Camps', ['Marnock Identity', 'Marnock Camps'], 'Marnock Camps'],
    'two equally good matches settle nothing' => ['Direct Credit 301500 SUNDERMERE LIQUOR', ['Sundermere Liquor Piara Waters', 'Sundermere Liquor'], null],
    'a word inside a longer word does not count' => ['Direct Credit 471076 Mungalalu North 431999', ['Northdandalup Holdings'], null],
    'bank and company boilerplate identify nobody' => ['Fast Transfer From Kellenbrook Joinery Pty Ltd', ['Fast Settlements', 'Elmsworth Pty Ltd'], null],
    'a short word alone, repeated' => ['Direct Credit 301500 Yolm Diamond To Yolm', ['Yolm Trading'], 'Yolm Trading'],
    'a short word alone, once' => ['Fast Transfer From Petrichor Yolm', ['Yolm Trading'], null],
    "the team's own name" => ['Transfer from Cebugle savings', ['Cebugle Holdings'], null],
]);

it('only considers active customers billed in the account currency', function (): void {
    expect(match_customer('Direct Credit 062000 HARBOUR DENTAL', function (string $teamId): void {
        matcher_customer($teamId, 'Harbour Dental', ['currency' => 'NZD']);
        matcher_customer($teamId, 'Harbour Dental Group', ['status' => 'inactive']);
    }))->toBeNull();
});
