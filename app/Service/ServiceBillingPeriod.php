<?php

declare(strict_types=1);

namespace App\Service;

use App\Rate\Rate;
use Carbon\CarbonImmutable;

/**
 * One stretch of days a service is billed for, and what one unit of it costs.
 *
 * A period runs from its first day to the end of the month it closes in, so
 * every period after the first starts on the 1st. A service that starts part
 * way through a month pays for the days left in it, when its rate prorates,
 * and then for whole months: one starting on 19 November, billed monthly,
 * pays for 19–30 November, then December, and so on.
 */
final readonly class ServiceBillingPeriod
{
    public function __construct(
        public CarbonImmutable $startsOn,
        public CarbonImmutable $endsOn,
        public int $unitPriceInCents,
    ) {}

    /**
     * The period a service is billed for next: from its billing start when it
     * was never billed, otherwise from the day after it was last billed to.
     */
    public static function next(Service $service, Rate $rate, ?CarbonImmutable $billedThrough): self
    {
        $startsOn = $billedThrough === null
            ? $service->getBillingStartsOn()->toCarbon()->startOfDay()
            : $billedThrough->addDay()->startOfDay();

        if ($billedThrough !== null && $startsOn->day !== 1) {
            $startsOn = $startsOn->startOfMonth()->addMonth();
        }

        return self::startingOn($startsOn, $service->billingPeriodInMonthsOn($rate), $rate);
    }

    /**
     * Every period of a service that starts on or before a day, oldest first,
     * so a service never billed since it started catches up. At most $limit.
     *
     * @return list<self>
     */
    public static function dueBy(Service $service, Rate $rate, ?CarbonImmutable $billedThrough, CarbonImmutable $day, int $limit = 24): array
    {
        $periods = [];
        $period = self::next($service, $rate, $billedThrough);

        while ($period->startsOn->lte($day) && count($periods) < $limit) {
            $periods[] = $period;
            $period = self::next($service, $rate, $period->endsOn);
        }

        return $periods;
    }

    private static function startingOn(CarbonImmutable $startsOn, int $months, Rate $rate): self
    {
        $monthlyPrice = $rate->getPriceInCents()->toInteger();
        $endsOn = $startsOn->startOfMonth()->addMonthsNoOverflow($months)->subDay();
        $isPartMonth = $startsOn->day !== 1 && $rate->getProratesFirstMonth()->toBoolean();

        $firstMonth = $isPartMonth
            ? (int) round($monthlyPrice * ($startsOn->daysInMonth - $startsOn->day + 1) / $startsOn->daysInMonth)
            : $monthlyPrice;

        return new self($startsOn, $endsOn, $firstMonth + $monthlyPrice * ($months - 1));
    }

    /**
     * The period as the pages show it.
     *
     * @return array{starts_on: string, ends_on: string, unit_price_in_cents: int}
     */
    public function toArray(): array
    {
        return [
            'starts_on' => $this->startsOn->toDateString(),
            'ends_on' => $this->endsOn->toDateString(),
            'unit_price_in_cents' => $this->unitPriceInCents,
        ];
    }
}
