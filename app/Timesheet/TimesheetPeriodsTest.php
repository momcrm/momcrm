<?php

declare(strict_types=1);

use App\Timesheet\TimesheetPeriodEnum;
use App\Timesheet\TimesheetPeriods;
use Carbon\CarbonImmutable;

it('puts a day in the week from its Monday', function (): void {
    $weeks = new TimesheetPeriods(TimesheetPeriodEnum::Weekly, CarbonImmutable::parse('2024-01-01'));

    expect($weeks->containing('2026-11-29'))->toBe(['starts_on' => '2026-11-23', 'ends_on' => '2026-11-29'])
        ->and($weeks->startsOn('2026-11-23'))->toBeTrue()
        ->and($weeks->startsOn('2026-11-24'))->toBeFalse()
        ->and($weeks->startsOn('not a date'))->toBeFalse()
        ->and($weeks->before('2026-11-23'))->toBe(['starts_on' => '2026-11-16', 'ends_on' => '2026-11-22']);
});

it('counts fortnights from the team’s Monday, before it and after it', function (): void {
    $fortnights = new TimesheetPeriods(TimesheetPeriodEnum::Fortnightly, CarbonImmutable::parse('2026-11-16'));

    expect($fortnights->containing('2026-11-23'))->toBe(['starts_on' => '2026-11-16', 'ends_on' => '2026-11-29'])
        ->and($fortnights->containing('2026-11-30'))->toBe(['starts_on' => '2026-11-30', 'ends_on' => '2026-12-13'])
        ->and($fortnights->containing('2026-11-15'))->toBe(['starts_on' => '2026-11-02', 'ends_on' => '2026-11-15'])
        ->and($fortnights->startsOn('2026-11-23'))->toBeFalse()
        ->and(array_column($fortnights->recent('2026-12-01', 3), 'starts_on'))->toBe(['2026-11-30', '2026-11-16', '2026-11-02']);
});
