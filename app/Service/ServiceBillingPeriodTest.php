<?php

declare(strict_types=1);

use App\Rate\Rate;
use App\Rate\RatePrice;
use App\Service\Service;
use App\Service\ServiceBillingPeriod;
use App\Shared\CurrencyEnum;
use Carbon\CarbonImmutable;

function billingRate(int $months = 1, bool $prorates = true): Rate
{
    return Rate::new()->setBillingPeriodInMonths($months)->setProratesFirstMonth($prorates);
}

function monthlyPrice(): RatePrice
{
    return new RatePrice(CurrencyEnum::AUD, 3000);
}

function billedService(string $startsOn, ?int $months = null): Service
{
    return Service::new()->setBillingStartsOn($startsOn)->setBillingPeriodInMonths($months)->setQuantity(1);
}

function day(string $date): CarbonImmutable
{
    return CarbonImmutable::parse($date);
}

it('bills a whole first month from its billing start on the 1st', function (): void {
    expect(ServiceBillingPeriod::next(billedService('2026-12-01'), billingRate(), monthlyPrice(), null)->toArray())
        ->toBe(['starts_on' => '2026-12-01', 'ends_on' => '2026-12-31', 'unit_price_in_cents' => 3000]);
});

it('charges only the days left when it starts part way through a month', function (): void {
    expect(ServiceBillingPeriod::next(billedService('2026-11-19'), billingRate(), monthlyPrice(), null)->toArray())
        ->toBe(['starts_on' => '2026-11-19', 'ends_on' => '2026-11-30', 'unit_price_in_cents' => 1200]);
});

it('charges the whole first month when the rate does not prorate', function (): void {
    expect(ServiceBillingPeriod::next(billedService('2026-11-19'), billingRate(prorates: false), monthlyPrice(), null)->unitPriceInCents)->toBe(3000);
});

it("bills several months at once, by the service's own period over the rate's", function (): void {
    expect(ServiceBillingPeriod::next(billedService('2026-11-19', months: 12), billingRate(months: 3), monthlyPrice(), null)->toArray())
        ->toBe(['starts_on' => '2026-11-19', 'ends_on' => '2027-10-31', 'unit_price_in_cents' => 1200 + 3000 * 11])
        ->and(ServiceBillingPeriod::next(billedService('2026-12-01'), billingRate(months: 3), monthlyPrice(), null)->endsOn->toDateString())->toBe('2027-02-28');
});

it('carries on the day after it was last billed to, from the 1st of a month', function (string $billedThrough, string $startsOn): void {
    expect(ServiceBillingPeriod::next(billedService('2026-01-01'), billingRate(), monthlyPrice(), day($billedThrough))->startsOn->toDateString())->toBe($startsOn);
})->with([
    'the end of a month' => ['2026-11-30', '2026-12-01'],
    'part way through a month' => ['2026-11-14', '2026-12-01'],
]);

it('lists every period that has started by a day, so a late service catches up', function (): void {
    $periods = ServiceBillingPeriod::dueBy(billedService('2026-09-15'), billingRate(), monthlyPrice(), null, day('2026-12-02'));

    expect(array_map(fn (ServiceBillingPeriod $period): string => $period->startsOn->toDateString(), $periods))
        ->toBe(['2026-09-15', '2026-10-01', '2026-11-01', '2026-12-01']);
});

it('lists nothing while a prepaid period still runs or before billing starts', function (string $startsOn, ?string $billedThrough): void {
    expect(ServiceBillingPeriod::dueBy(billedService($startsOn), billingRate(), monthlyPrice(), $billedThrough === null ? null : day($billedThrough), day('2026-12-02')))->toBe([]);
})->with([
    'prepaid to next year' => ['2026-01-01', '2027-06-30'],
    'starting next month' => ['2027-01-01', null],
]);
