<?php

declare(strict_types=1);

use App\Customer\BpayReference;

it('adds a MOD10V01 check digit to the customer number', function (int $number, string $reference): void {
    expect(BpayReference::forCustomerNumber($number))->toBe($reference)
        ->and(BpayReference::customerNumberOf($reference))->toBe($number);
})->with([
    [1, '18'],
    [42, '422'],
    [7992739871, '79927398713'],
]);

it('reads back only references with a right check digit', function (string $reference, ?int $number): void {
    expect(BpayReference::customerNumberOf($reference))->toBe($number);
})->with([
    'leading zeros, as BPAY files pad them' => ['000422', 42],
    'a wrong check digit' => ['423', null],
    'not digits' => ['42A', null],
    'too short' => ['4', null],
]);
