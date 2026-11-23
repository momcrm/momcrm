<?php

declare(strict_types=1);

namespace App\Timesheet;

use App\Timesheet\Actions\FindTimesheet;

/**
 * Whether a member's time on a day may still change. An approved timesheet
 * locks its days for everyone; a submitted one also stops the member adding
 * time themselves while it waits for approval.
 */
final class TimesheetLock
{
    public static function isLocked(string $teamId, string $userId, string $date, bool $byMember): bool
    {
        $timesheet = FindTimesheet::coveringForMember($teamId, $userId, $date);

        if ($timesheet->isNull()) {
            return false;
        }

        $timesheetStatus = $timesheet->getStatus()->toEnum();

        return $byMember ? $timesheetStatus->locksMember() : $timesheetStatus === TimesheetStatusEnum::Approved;
    }
}
