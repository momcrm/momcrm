import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import AttendanceTabs from '@/components/attendance/attendance-tabs';
import DayStatus from '@/components/attendance/day-status';
import PageHeader from '@/components/page-header';
import StatusPill from '@/components/status-pill';
import { AssigneeAvatar } from '@/components/tickets/ticket-meta';
import {
    formatClockTime,
    formatDuration,
    secondsSince,
    useNow,
} from '@/lib/attendance';
import { formatDay } from '@/lib/dates';
import { index as attendanceIndex, time } from '@/routes/attendance';
import type { AttendanceBoardRow, AttendanceStatus, Tone } from '@/types';

const groups: {
    status: AttendanceStatus;
    title: string;
    tone: Tone;
}[] = [
    { status: 'in', title: 'In', tone: 'success' },
    { status: 'break', title: 'On a break', tone: 'warning' },
    { status: 'not_in', title: 'Not in yet', tone: 'neutral' },
    { status: 'out', title: 'Finished', tone: 'info' },
    { status: 'off', title: 'Off today', tone: 'neutral' },
];

/**
 * What a row says about the member's day so far.
 */
function detail(row: AttendanceBoardRow, now: number): string {
    switch (row.status) {
        case 'in':
            return `In since ${formatClockTime(row.clocked_in_at ?? '')}`;
        case 'break':
            return `On a break for ${formatDuration(secondsSince(row.on_break_since ?? '', now))}`;
        case 'out':
            return `${formatClockTime(row.first_in_at ?? '')} – ${formatClockTime(row.last_out_at ?? '')}`;
        case 'off':
            return row.day?.holiday ?? 'Day off';
        default:
            return row.day?.expected_starts_at
                ? `Starts ${formatClockTime(row.day.expected_starts_at)}`
                : '—';
    }
}

/**
 * Who is working today: everyone in the team grouped by whether they are
 * in, on a break, not in yet or finished, with their hours so far.
 */
export default function AttendanceToday({
    today,
    members,
    canManage,
}: {
    today: string;
    members: AttendanceBoardRow[];
    canManage: boolean;
}) {
    const working = members.some(
        (row) => row.status === 'in' || row.status === 'break',
    );
    const now = useNow(working);
    const [loadedAt, setLoadedAt] = useState(() => Date.now());
    const [shownMembers, setShownMembers] = useState(members);

    // Hours on the board were counted when the page loaded; tick on from then.
    if (shownMembers !== members) {
        setShownMembers(members);
        setLoadedAt(Date.now());
    }

    /** Hours worked, ticking for anyone still on the clock. */
    const worked = (row: AttendanceBoardRow): number =>
        row.worked_seconds +
        (row.status === 'in' ? Math.floor((now - loadedAt) / 1000) : 0);

    const inCount = members.filter(
        (row) => row.status === 'in' || row.status === 'break',
    ).length;

    return (
        <>
            <Head title="Attendance" />

            <div className="mx-auto flex w-full max-w-4xl flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Attendance"
                    description={`${formatDay(today)} · ${inCount} of ${members.length} working now`}
                />

                <AttendanceTabs current="today" />

                {groups.map((group) => {
                    const rows = members.filter(
                        (row) => row.status === group.status,
                    );

                    if (rows.length === 0) {
                        return null;
                    }

                    return (
                        <section
                            key={group.status}
                            className="flex flex-col gap-2"
                        >
                            <h2 className="flex items-center gap-2 text-sm font-medium">
                                <StatusPill tone={group.tone}>
                                    {group.title}
                                </StatusPill>
                                <span className="text-muted-foreground tabular-nums">
                                    {rows.length}
                                </span>
                            </h2>
                            <ul className="divide-y overflow-hidden rounded-lg border bg-card shadow-xs">
                                {rows.map((row) => {
                                    const name = (
                                        <span className="flex min-w-0 items-center gap-2.5">
                                            <AssigneeAvatar name={row.name} />
                                            <span className="truncate font-medium">
                                                {row.name}
                                            </span>
                                        </span>
                                    );

                                    return (
                                        <li
                                            key={row.id}
                                            className="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 px-3 py-2.5 text-sm"
                                        >
                                            <span className="flex min-w-0 items-center gap-2">
                                                {canManage ? (
                                                    <Link
                                                        href={time({
                                                            query: {
                                                                member: row.id,
                                                            },
                                                        })}
                                                        className="min-w-0 hover:underline"
                                                    >
                                                        {name}
                                                    </Link>
                                                ) : (
                                                    name
                                                )}
                                                {row.day &&
                                                    row.status !== 'off' && (
                                                        <DayStatus
                                                            day={row.day}
                                                            showOnTime={false}
                                                        />
                                                    )}
                                            </span>
                                            <span className="flex items-center gap-4 text-muted-foreground">
                                                <span>{detail(row, now)}</span>
                                                <span className="w-16 text-right text-foreground tabular-nums">
                                                    {row.status === 'not_in' ||
                                                    row.status === 'off'
                                                        ? ''
                                                        : formatDuration(
                                                              worked(row),
                                                          )}
                                                </span>
                                            </span>
                                        </li>
                                    );
                                })}
                            </ul>
                        </section>
                    );
                })}
            </div>
        </>
    );
}

AttendanceToday.layout = {
    breadcrumbs: [{ title: 'Attendance', href: attendanceIndex() }],
};
