import { Head, Link, router, usePage } from '@inertiajs/react';
import { Check, ChevronLeft, ChevronRight, Lock, Undo2 } from 'lucide-react';
import { useState } from 'react';
import DayStatus from '@/components/attendance/day-status';
import SendBackDialog from '@/components/attendance/send-back-dialog';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import StatusPill from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import {
    describePeriod,
    formatClockTime,
    formatDuration,
    formatLeaveDays,
    timesheetStatuses,
} from '@/lib/attendance';
import { parseDay } from '@/lib/dates';
import {
    index as attendanceIndex,
    time,
    timesheet as timesheetRoute,
    timesheets as timesheetsRoute,
} from '@/routes/attendance';
import { approve, submit } from '@/routes/timesheets';
import type { TimesheetDay, TimesheetSummary } from '@/types';

const weekday = new Intl.DateTimeFormat(undefined, { weekday: 'short' });
const dayOfMonth = new Intl.DateTimeFormat(undefined, {
    day: 'numeric',
    month: 'short',
});

/**
 * One member's timesheet for a pay period, day by day against their
 * schedule. An approved one shows its days as they were approved.
 */
export default function AttendanceTimesheet({
    member,
    isYou,
    timesheet,
    days,
    canManage,
    canSubmit,
}: {
    member: { id: string; name: string; timezone: string };
    isYou: boolean;
    timesheet: TimesheetSummary & {
        previous: string;
        next: string;
        is_kept: boolean;
    };
    days: TimesheetDay[];
    canManage: boolean;
    canSubmit: boolean;
}) {
    const { errors } = usePage().props;
    const [sendingBack, setSendingBack] = useState(false);
    const status = timesheetStatuses[timesheet.status];
    const period = describePeriod(timesheet.starts_on, timesheet.ends_on);
    const weeks = days
        .filter((_, index) => index % 7 === 0)
        .map((day) => day.date);

    return (
        <>
            <Head title={`Timesheet · ${period}`} />

            <div className="mx-auto flex w-full max-w-4xl flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title={
                        isYou ? 'Your timesheet' : `${member.name}’s timesheet`
                    }
                    description={[
                        period,
                        `${formatDuration(timesheet.worked_seconds)} worked${timesheet.expected_seconds > 0 ? ` of ${formatDuration(timesheet.expected_seconds)}` : ''}`,
                        timesheet.overtime_seconds > 0 &&
                            `${formatDuration(timesheet.overtime_seconds)} overtime`,
                        timesheet.leave_days > 0 &&
                            `${formatLeaveDays(timesheet.leave_days)} leave`,
                    ]
                        .filter(Boolean)
                        .join(' · ')}
                    actions={
                        <div className="flex items-center gap-2">
                            <StatusPill tone={status.tone}>
                                {status.label}
                            </StatusPill>
                            {canSubmit && (
                                <Button
                                    onClick={() =>
                                        router.post(
                                            submit.url(),
                                            { starts_on: timesheet.starts_on },
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    Submit
                                </Button>
                            )}
                            {canManage &&
                                timesheet.status === 'submitted' &&
                                timesheet.timesheet_id && (
                                    <>
                                        <Button
                                            variant="outline"
                                            onClick={() => setSendingBack(true)}
                                        >
                                            <Undo2 />
                                            Send back
                                        </Button>
                                        <Button
                                            onClick={() =>
                                                router.post(
                                                    approve.url(
                                                        timesheet.timesheet_id ??
                                                            '',
                                                    ),
                                                    {},
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            <Check />
                                            Approve
                                        </Button>
                                    </>
                                )}
                        </div>
                    }
                />

                <div className="flex flex-wrap items-center gap-1">
                    <Button
                        variant="outline"
                        size="icon"
                        className="size-8"
                        asChild
                    >
                        <Link
                            href={timesheetRoute([
                                member.id,
                                timesheet.previous,
                            ])}
                            aria-label="Previous period"
                        >
                            <ChevronLeft />
                        </Link>
                    </Button>
                    <Button
                        variant="outline"
                        size="icon"
                        className="size-8"
                        asChild
                    >
                        <Link
                            href={timesheetRoute([member.id, timesheet.next])}
                            aria-label="Next period"
                        >
                            <ChevronRight />
                        </Link>
                    </Button>
                    <Link
                        href={timesheetsRoute()}
                        className="ml-2 text-sm text-muted-foreground hover:text-foreground hover:underline"
                    >
                        All timesheets
                    </Link>
                    {!timesheet.is_kept && (
                        <span className="ml-auto flex gap-3 text-sm">
                            {weeks.map((monday) => (
                                <Link
                                    key={monday}
                                    href={time({
                                        query: {
                                            ...(isYou
                                                ? {}
                                                : { member: member.id }),
                                            week: monday,
                                        },
                                    })}
                                    className="text-muted-foreground hover:text-foreground hover:underline"
                                >
                                    Shifts from{' '}
                                    {dayOfMonth.format(parseDay(monday))}
                                </Link>
                            ))}
                        </span>
                    )}
                </div>

                <InputError message={errors.starts_on ?? errors.timesheet} />

                {timesheet.is_kept && (
                    <p className="flex items-center gap-2 rounded-lg border bg-muted/40 p-3 text-sm text-muted-foreground">
                        <Lock className="size-4" />
                        Approved
                        {timesheet.reviewed_by_name &&
                            ` by ${timesheet.reviewed_by_name}`}
                        . These are the days as they were approved, and their
                        shifts can no longer change.
                    </p>
                )}
                {timesheet.status === 'rejected' && timesheet.review_note && (
                    <p className="rounded-lg border border-destructive/30 bg-destructive/5 p-3 text-sm">
                        <span className="font-medium">
                            {timesheet.reviewed_by_name ?? 'Sent back'}:
                        </span>{' '}
                        {timesheet.review_note}
                    </p>
                )}

                <div className="overflow-hidden rounded-lg border bg-card shadow-xs">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/40 text-xs text-muted-foreground">
                            <tr>
                                <th className="px-3 py-2 text-left font-medium">
                                    Day
                                </th>
                                <th className="px-3 py-2 text-left font-medium">
                                    Scheduled
                                </th>
                                <th className="px-3 py-2 text-left font-medium">
                                    How it went
                                </th>
                                <th className="px-3 py-2 text-right font-medium">
                                    Expected
                                </th>
                                <th className="px-3 py-2 text-right font-medium">
                                    Worked
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {days.map((day) => (
                                <tr key={day.date}>
                                    <td className="px-3 py-2 whitespace-nowrap">
                                        <span className="inline-block w-9 font-medium">
                                            {weekday.format(parseDay(day.date))}
                                        </span>
                                        <span className="text-muted-foreground">
                                            {dayOfMonth.format(
                                                parseDay(day.date),
                                            )}
                                        </span>
                                    </td>
                                    <td className="px-3 py-2 whitespace-nowrap text-muted-foreground tabular-nums">
                                        {day.expected_starts_at &&
                                        day.expected_ends_at
                                            ? `${formatClockTime(day.expected_starts_at, member.timezone)}–${formatClockTime(day.expected_ends_at, member.timezone)}`
                                            : '—'}
                                    </td>
                                    <td className="px-3 py-2">
                                        <DayStatus day={day} />
                                    </td>
                                    <td className="px-3 py-2 text-right text-muted-foreground tabular-nums">
                                        {day.expected_seconds > 0
                                            ? formatDuration(
                                                  day.expected_seconds,
                                              )
                                            : ''}
                                    </td>
                                    <td className="px-3 py-2 text-right font-medium tabular-nums">
                                        {day.worked_seconds > 0
                                            ? formatDuration(day.worked_seconds)
                                            : ''}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {sendingBack && timesheet.timesheet_id && (
                <SendBackDialog
                    timesheetId={timesheet.timesheet_id}
                    name={member.name}
                    period={period}
                    onClose={() => setSendingBack(false)}
                />
            )}
        </>
    );
}

AttendanceTimesheet.layout = {
    breadcrumbs: [
        { title: 'Attendance', href: attendanceIndex() },
        { title: 'Timesheets', href: timesheetsRoute() },
    ],
};
