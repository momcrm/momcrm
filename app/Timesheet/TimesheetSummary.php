<?php

declare(strict_types=1);

namespace App\Timesheet;

use App\Attendance\AttendanceCalendar;

/**
 * One member's period as the timesheet pages list it: where its timesheet
 * is, and what the period adds up to (as approved, or as it is now).
 */
final class TimesheetSummary
{
    /**
     * @param  array{starts_on: string, ends_on: string}  $period
     * @param  array<string, string>  $names  user id => name
     * @return array{starts_on: string, ends_on: string, timesheet_id: ?string, status: string, submitted_at: ?string, reviewed_by_name: ?string, review_note: ?string, worked_seconds: int, expected_seconds: int, overtime_seconds: int, leave_days: float, late_days: int, absent_days: int}
     */
    public static function toArray(Timesheet $timesheet, string $teamId, string $userId, array $period, AttendanceCalendar $calendar, array $names = []): array
    {
        $reviewer = $timesheet->getReviewedBy()->toNullableString();

        return [
            'starts_on' => $period['starts_on'],
            'ends_on' => $period['ends_on'],
            'timesheet_id' => $timesheet->getId()->toNullableString(),
            'status' => $timesheet->isNull() ? 'open' : $timesheet->getStatus()->toEnum()->value,
            'submitted_at' => $timesheet->getSubmittedAt()->toNullableISOString(),
            'reviewed_by_name' => $reviewer === null ? null : ($names[$reviewer] ?? null),
            'review_note' => $timesheet->getReviewNote()->toNullableString(),
            ...TimesheetTotals::headline(TimesheetTotals::of($timesheet, $teamId, $userId, $period['starts_on'], $period['ends_on'], $calendar)),
        ];
    }
}
