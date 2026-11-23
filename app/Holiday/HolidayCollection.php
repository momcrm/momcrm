<?php

declare(strict_types=1);

namespace App\Holiday;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Holiday>
 */
final class HolidayCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Holiday::class;
    }

    /**
     * The holiday's name on this date (YYYY-MM-DD), or null on a normal day.
     */
    public function nameOn(string $date): ?string
    {
        return $this->first(fn (Holiday $holiday): bool => $holiday->getDate()->toDateString() === $date)->getName()->toNullableString();
    }
}
