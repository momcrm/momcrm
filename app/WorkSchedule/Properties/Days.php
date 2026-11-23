<?php

declare(strict_types=1);

namespace App\WorkSchedule\Properties;

use App\WorkSchedule\WorkScheduleDays;
use Mom\Data\AbstractCollection;

/**
 * Each weekday's hours, keyed mon to sun: {starts_at, ends_at, break_minutes},
 * or null for a day off.
 *
 * @phpstan-import-type Day from WorkScheduleDays
 */
final class Days extends AbstractCollection
{
    public static function getName(): string
    {
        return 'days';
    }

    /**
     * @return array<string, Day|null>
     */
    public function toDays(): array
    {
        return WorkScheduleDays::fromInput($this->toCollection()->all());
    }
}
