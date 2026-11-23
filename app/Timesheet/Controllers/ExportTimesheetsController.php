<?php

declare(strict_types=1);

namespace App\Timesheet\Controllers;

use App\Attendance\AttendanceCalendar;
use App\Attendance\AttendanceTimezone;
use App\Team\Exceptions\CouldNotViewTeam;
use App\Timesheet\Actions\FindTimesheet;
use App\Timesheet\TimesheetAccess;
use App\Timesheet\TimesheetPeriods;
use App\Timesheet\TimesheetTotals;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Everyone's days in one pay period as a CSV file for payroll: one row per
 * member per day, hours as decimals. Approved timesheets give their days as
 * approved. Owners and admins only.
 */
final class ExportTimesheetsController
{
    /**
     * @throws CouldNotViewTeam
     */
    public function __invoke(Request $request): StreamedResponse
    {
        $team = TimesheetAccess::team($request);
        Gate::authorize('manageAttendance', $team);

        $teamId = $team->getId()->toString();
        $periods = TimesheetPeriods::forTeam($team);
        $requested = $request->string('period')->toString();
        $period = $periods->startsOn($requested) ? $periods->containing($requested) : $periods->containing(now()->toDateString());
        $members = AttendanceTimezone::membersOfTeam($teamId);
        $emails = UserEloquentModel::query()->whereIn('id', $members->pluck('id')->all())->pluck('email', 'id')->all();
        $calendar = AttendanceCalendar::forTeam($teamId, $members, $period['starts_on'], $period['ends_on']);
        $timesheets = FindTimesheet::startingBetweenForTeam($teamId, $period['starts_on'], $period['starts_on']);
        $hours = fn (mixed $seconds): string => number_format((is_int($seconds) ? $seconds : 0) / 3600, 2, '.', '');

        return response()->streamDownload(function () use ($members, $emails, $timesheets, $teamId, $period, $calendar, $hours): void {
            $file = fopen('php://output', 'w');

            if ($file === false) {
                return;
            }

            fputcsv($file, ['Member', 'Email', 'Date', 'Status', 'Flags', 'Leave', 'Expected hours', 'Worked hours', 'Break hours', 'Overtime hours', 'Timesheet'], escape: '');

            foreach ($members as $member) {
                $timesheet = $timesheets->forMember($member['id'], $period['starts_on']);
                $totals = TimesheetTotals::of($timesheet, $teamId, $member['id'], $period['starts_on'], $period['ends_on'], $calendar);
                $timesheetStatus = $timesheet->isNull() ? 'open' : $timesheet->getStatus()->toEnum()->value;

                foreach (is_array($totals['days'] ?? null) ? $totals['days'] : [] as $day) {
                    if (! is_array($day)) {
                        continue;
                    }

                    fputcsv($file, [
                        self::cell($member['name']),
                        self::cell(is_string($emails[$member['id']] ?? null) ? $emails[$member['id']] : ''),
                        is_string($day['date'] ?? null) ? $day['date'] : '',
                        is_string($day['status'] ?? null) ? $day['status'] : '',
                        is_array($day['flags'] ?? null) ? implode(' ', array_filter($day['flags'], is_string(...))) : '',
                        self::cell(is_string($day['leave'] ?? null) ? $day['leave'] : (is_string($day['holiday'] ?? null) ? $day['holiday'] : '')),
                        $hours($day['expected_seconds'] ?? 0),
                        $hours($day['worked_seconds'] ?? 0),
                        $hours($day['break_seconds'] ?? 0),
                        $hours($day['overtime_seconds'] ?? 0),
                        $timesheetStatus,
                    ], escape: '');
                }
            }

            fclose($file);
        }, "timesheets-{$period['starts_on']}.csv", ['Content-Type' => 'text/csv']);
    }

    /**
     * Text a person typed, kept from being read as a formula by spreadsheets.
     */
    private static function cell(string $text): string
    {
        return in_array(mb_substr($text, 0, 1), ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$text : $text;
    }
}
