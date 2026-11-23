<?php

declare(strict_types=1);

namespace App\Timesheet;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Timesheet>
 */
final class TimesheetCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Timesheet::class;
    }

    /**
     * The member's timesheet for the period starting on this date, or an empty one.
     */
    public function forMember(string $userId, string $startsOn): Timesheet
    {
        return $this->first(fn (Timesheet $timesheet): bool => $timesheet->getUserId()->toNullableString() === $userId
            && $timesheet->getStartsOn()->toDateString() === $startsOn);
    }
}
