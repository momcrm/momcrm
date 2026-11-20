<?php

declare(strict_types=1);

use App\Customer\Customer;
use App\Invoice\PaymentTerms;
use App\Team\Team;
use Carbon\CarbonImmutable;

it("dues an invoice by the customer's own days to pay, else the team's", function (?int $customerDays, ?int $teamDays, string $due): void {
    $customer = Customer::new()->setDaysToPay($customerDays);
    $team = Team::new()->setDaysToPay($teamDays);

    expect(PaymentTerms::dueOn(CarbonImmutable::parse('2026-11-20'), $customer, $team)->toDateString())->toBe($due);
})->with([
    'their own' => [7, 30, '2026-11-27'],
    'on receipt' => [0, 30, '2026-11-20'],
    "the team's" => [null, 30, '2026-12-20'],
    'neither set' => [null, null, '2026-12-04'],
]);
