<?php

declare(strict_types=1);

namespace App\Timesheet;

use App\Team\Team;
use Carbon\CarbonImmutable;

/**
 * A team's pay periods: weeks from Monday, or fortnights counted from the
 * Monday the team set (any Monday will do until it sets one).
 */
final readonly class TimesheetPeriods
{
    private const string DEFAULT_ANCHOR = '2024-01-01';

    public function __construct(
        public TimesheetPeriodEnum $period,
        private CarbonImmutable $anchor,
    ) {}

    public static function forTeam(Team $team): self
    {
        return new self(
            $team->getTimesheetPeriod()->toEnum(),
            ($team->getTimesheetStartsOn()->toNullableCarbon() ?? CarbonImmutable::parse(self::DEFAULT_ANCHOR))->startOfDay()->startOfWeek(CarbonImmutable::MONDAY),
        );
    }

    /**
     * The period holding this date (YYYY-MM-DD).
     *
     * @return array{starts_on: string, ends_on: string}
     */
    public function containing(string $date): array
    {
        $monday = CarbonImmutable::parse($date)->startOfWeek(CarbonImmutable::MONDAY);
        $offset = (int) $this->anchor->diffInDays($monday, false);
        $startsOn = $monday->subDays((($offset % $this->period->days()) + $this->period->days()) % $this->period->days());

        return $this->startingAt($startsOn);
    }

    /**
     * Whether a period starts on this date.
     */
    public function startsOn(string $date): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 && $this->containing($date)['starts_on'] === $date;
    }

    /**
     * The period before the one starting on this date.
     *
     * @return array{starts_on: string, ends_on: string}
     */
    public function before(string $startsOn): array
    {
        return $this->startingAt(CarbonImmutable::parse($startsOn)->subDays($this->period->days()));
    }

    /**
     * The period after the one starting on this date.
     *
     * @return array{starts_on: string, ends_on: string}
     */
    public function after(string $startsOn): array
    {
        return $this->startingAt(CarbonImmutable::parse($startsOn)->addDays($this->period->days()));
    }

    /**
     * The period holding this date and the ones before it, latest first.
     *
     * @return list<array{starts_on: string, ends_on: string}>
     */
    public function recent(string $today, int $count): array
    {
        $periods = [$this->containing($today)];

        while (count($periods) < $count) {
            $periods[] = $this->before($periods[count($periods) - 1]['starts_on']);
        }

        return $periods;
    }

    /**
     * @return array{starts_on: string, ends_on: string}
     */
    private function startingAt(CarbonImmutable $startsOn): array
    {
        return [
            'starts_on' => $startsOn->toDateString(),
            'ends_on' => $startsOn->addDays($this->period->days() - 1)->toDateString(),
        ];
    }
}
