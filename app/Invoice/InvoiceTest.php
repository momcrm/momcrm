<?php

declare(strict_types=1);

use App\Invoice\Invoice;

it('reads the invoice number from a search, whatever the prefix', function (string $search, ?int $number): void {
    expect(Invoice::numberIn($search))->toBe($number);
})->with([
    'bare number' => ['42', 42],
    'with the default prefix' => ['INV-0042', 42],
    'with digits in the prefix' => ['INV2026-0042', 42],
    'no digits' => ['INV-', null],
]);

it('prints the number with the prefix it was drafted with', function (?string $prefix, string $reference): void {
    expect(Invoice::new()->setPrefix($prefix)->setNumber(42)->getReference())->toBe($reference);
})->with([
    'a prefix' => ['CEB-', 'CEB-0042'],
    'no prefix' => ['', '0042'],
]);
