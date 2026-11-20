<?php

declare(strict_types=1);

use App\Customer\Customer;

it('prints a tax ID with what it is called', function (?string $label, ?string $taxId, ?string $line): void {
    expect(Customer::new()->setTaxIdLabel($label)->setTaxId($taxId)->taxIdLine())->toBe($line);
})->with([
    'named' => ['VAT', 'GB123456789', 'VAT GB123456789'],
    'unnamed' => [null, '12-3456789', 'Tax ID 12-3456789'],
    'none' => ['ABN', null, null],
]);
