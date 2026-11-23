<?php

declare(strict_types=1);

namespace App\WorkSchedule;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, WorkSchedule>
 */
final class WorkScheduleCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return WorkSchedule::class;
    }

    /**
     * The team's default schedule, or an empty one when it has none.
     */
    public function defaultSchedule(): WorkSchedule
    {
        return $this->first(fn (WorkSchedule $schedule): bool => $schedule->getIsDefault()->toBoolean());
    }
}
