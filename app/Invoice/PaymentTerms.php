<?php

declare(strict_types=1);

namespace App\Invoice;

use App\Customer\Customer;
use App\Team\Properties\DaysToPay;
use App\Team\Team;
use Carbon\CarbonImmutable;

/**
 * How long a customer has to pay an invoice: their own days to pay when
 * they have one, otherwise their team's.
 */
final class PaymentTerms
{
    public static function daysFor(Customer $customer, Team $team): int
    {
        return $customer->getDaysToPay()->toNullableInteger()
            ?? $team->getDaysToPay()->toNullableInteger()
            ?? DaysToPay::DEFAULT;
    }

    public static function dueOn(CarbonImmutable $issuedOn, Customer $customer, Team $team): CarbonImmutable
    {
        return $issuedOn->addDays(self::daysFor($customer, $team));
    }
}
