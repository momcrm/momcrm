import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Check,
    ChevronLeft,
    ChevronRight,
    Download,
    Undo2,
} from 'lucide-react';
import { useState } from 'react';
import AttendanceTabs from '@/components/attendance/attendance-tabs';
import SendBackDialog from '@/components/attendance/send-back-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import StatusPill from '@/components/status-pill';
import { AssigneeAvatar } from '@/components/tickets/ticket-meta';
import { Button } from '@/components/ui/button';
import {
    describePeriod,
    formatDuration,
    formatLeaveDays,
    timesheetStatuses,
} from '@/lib/attendance';
import {
    index as attendanceIndex,
    timesheet as timesheetRoute,
    timesheets as timesheetsRoute,
} from '@/routes/attendance';
import { exportMethod } from '@/routes/attendance/timesheets';
import { approve, submit } from '@/routes/timesheets';
import type { TimesheetSummary } from '@/types';

type TeamRow = TimesheetSummary & { user_id: string; name: string };

/**
 * What a period adds up to, in a line: hours against expected, overtime,
 * leave, and days late or absent.
 */
function Totals({ summary }: { summary: TimesheetSummary }) {
    return (
        <span className="truncate text-muted-foreground">
            {[
                `${formatDuration(summary.worked_seconds)}${summary.expected_seconds > 0 ? ` of ${formatDuration(summary.expected_seconds)}` : ''}`,
                summary.overtime_seconds > 0 &&
                    `${formatDuration(summary.overtime_seconds)} overtime`,
                summary.leave_days > 0 &&
                    `${formatLeaveDays(summary.leave_days)} leave`,
                summary.late_days > 0 && `${summary.late_days} late`,
                summary.absent_days > 0 && `${summary.absent_days} absent`,
            ]
                .filter(Boolean)
                .join(' · ')}
        </span>
    );
}

function Status({ summary }: { summary: TimesheetSummary }) {
    const status = timesheetStatuses[summary.status];

    return <StatusPill tone={status.tone}>{status.label}</StatusPill>;
}

/**
 * Timesheets: your recent pay periods to submit, and for owners and admins
 * everyone's timesheets for a period, to approve and export for payroll.
 */
export default function AttendanceTimesheets({
    periodLength,
    mine,
    team,
    canManage,
}: {
    today: string;
    periodLength: 'weekly' | 'fortnightly';
    mine: (TimesheetSummary & { can_submit: boolean })[];
    team: {
        period: {
            starts_on: string;
            ends_on: string;
            previous: string;
            next: string;
        };
        rows: TeamRow[];
    } | null;
    canManage: boolean;
}) {
    const { auth, errors } = usePage().props;
    const [sendingBack, setSendingBack] = useState<TeamRow | null>(null);
    const userId = auth.user?.id;

    return (
        <>
            <Head title="Timesheets" />

            <div className="mx-auto flex w-full max-w-4xl flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Timesheets"
                    description={`Your hours for each ${periodLength === 'weekly' ? 'week' : 'fortnight'}, sent to an owner or admin to approve. Approving keeps the totals and locks the days.`}
                />

                <AttendanceTabs current="timesheets" />

                <InputError message={errors.starts_on ?? errors.timesheet} />

                {canManage && team && (
                    <section className="space-y-3">
                        <div className="flex flex-wrap items-center gap-2">
                            <Heading
                                variant="small"
                                title={`Team · ${describePeriod(team.period.starts_on, team.period.ends_on)}`}
                            />
                            <span className="ml-auto flex items-center gap-1">
                                <Button
                                    variant="outline"
                                    size="icon"
                                    className="size-8"
                                    asChild
                                >
                                    <Link
                                        href={timesheetsRoute({
                                            query: {
                                                period: team.period.previous,
                                            },
                                        })}
                                        aria-label="Previous period"
                                        preserveScroll
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
                                        href={timesheetsRoute({
                                            query: { period: team.period.next },
                                        })}
                                        aria-label="Next period"
                                        preserveScroll
                                    >
                                        <ChevronRight />
                                    </Link>
                                </Button>
                                <Button variant="outline" size="sm" asChild>
                                    <a
                                        href={
                                            exportMethod({
                                                query: {
                                                    period: team.period
                                                        .starts_on,
                                                },
                                            }).url
                                        }
                                    >
                                        <Download />
                                        Export CSV
                                    </a>
                                </Button>
                            </span>
                        </div>
                        <ul className="divide-y rounded-lg border bg-card shadow-xs">
                            {team.rows.map((row) => (
                                <li
                                    key={row.user_id}
                                    className="flex flex-wrap items-center gap-x-3 gap-y-1 px-3 py-2.5 text-sm"
                                >
                                    <Link
                                        href={timesheetRoute([
                                            row.user_id,
                                            row.starts_on,
                                        ])}
                                        className="flex w-40 min-w-0 items-center gap-2 hover:underline"
                                    >
                                        <AssigneeAvatar name={row.name} />
                                        <span className="truncate font-medium">
                                            {row.name}
                                        </span>
                                    </Link>
                                    <span className="min-w-0 flex-1">
                                        <Totals summary={row} />
                                    </span>
                                    <Status summary={row} />
                                    {row.status === 'submitted' &&
                                        row.timesheet_id && (
                                            <span className="flex gap-1">
                                                <Button
                                                    size="sm"
                                                    className="h-7"
                                                    onClick={() =>
                                                        router.post(
                                                            approve.url(
                                                                row.timesheet_id ??
                                                                    '',
                                                            ),
                                                            {},
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                >
                                                    <Check />
                                                    Approve
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="h-7"
                                                    onClick={() =>
                                                        setSendingBack(row)
                                                    }
                                                >
                                                    <Undo2 />
                                                    Send back
                                                </Button>
                                            </span>
                                        )}
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                <section className="space-y-3">
                    <Heading variant="small" title="Your timesheets" />
                    <ul className="divide-y rounded-lg border bg-card shadow-xs">
                        {mine.map((period) => (
                            <li
                                key={period.starts_on}
                                className="flex flex-wrap items-center gap-x-3 gap-y-1 px-3 py-2.5 text-sm"
                            >
                                <Link
                                    href={timesheetRoute([
                                        userId ?? '',
                                        period.starts_on,
                                    ])}
                                    className="w-36 font-medium hover:underline"
                                >
                                    {describePeriod(
                                        period.starts_on,
                                        period.ends_on,
                                    )}
                                </Link>
                                <span className="flex min-w-0 flex-1 flex-col">
                                    <Totals summary={period} />
                                    {period.status === 'rejected' &&
                                        period.review_note && (
                                            <span className="truncate text-xs text-destructive">
                                                {period.reviewed_by_name ??
                                                    'Sent back'}
                                                : {period.review_note}
                                            </span>
                                        )}
                                </span>
                                <Status summary={period} />
                                {period.can_submit && (
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        className="h-7"
                                        onClick={() =>
                                            router.post(
                                                submit.url(),
                                                {
                                                    starts_on: period.starts_on,
                                                },
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        Submit
                                    </Button>
                                )}
                            </li>
                        ))}
                    </ul>
                </section>
            </div>

            {sendingBack && sendingBack.timesheet_id && (
                <SendBackDialog
                    timesheetId={sendingBack.timesheet_id}
                    name={sendingBack.name}
                    period={describePeriod(
                        sendingBack.starts_on,
                        sendingBack.ends_on,
                    )}
                    onClose={() => setSendingBack(null)}
                />
            )}
        </>
    );
}

AttendanceTimesheets.layout = {
    breadcrumbs: [
        { title: 'Attendance', href: attendanceIndex() },
        { title: 'Timesheets', href: timesheetsRoute() },
    ],
};
