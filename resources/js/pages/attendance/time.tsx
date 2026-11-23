import { Head, Link, router } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    MoreHorizontal,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import AttendanceTabs from '@/components/attendance/attendance-tabs';
import ShiftDialog from '@/components/attendance/shift-dialog';
import ConfirmDelete from '@/components/confirm-delete';
import NativeSelect from '@/components/native-select';
import PageHeader from '@/components/page-header';
import StatusPill from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    formatClockTime,
    formatDuration,
    secondsSince,
    useNow,
} from '@/lib/attendance';
import { addDays, formatDay, parseDay } from '@/lib/dates';
import { cn } from '@/lib/utils';
import { index as attendanceIndex, time } from '@/routes/attendance';
import { destroy } from '@/routes/shifts';
import type { AttendanceMember, AttendanceWeek, ShiftSummary } from '@/types';

const weekday = new Intl.DateTimeFormat(undefined, { weekday: 'short' });
const dayOfMonth = new Intl.DateTimeFormat(undefined, {
    day: 'numeric',
    month: 'short',
});

type Editing = { shift?: ShiftSummary; date: string } | null;

/**
 * One shift's line: its times, breaks and time worked. A running shift keeps
 * counting.
 */
function ShiftRow({
    shift,
    timezone,
    now,
    loadedAt,
    canManage,
    onEdit,
    onDelete,
}: {
    shift: ShiftSummary;
    timezone: string;
    now: number;
    loadedAt: number;
    canManage: boolean;
    onEdit: () => void;
    onDelete: () => void;
}) {
    const onBreak = shift.on_break_since !== null;
    const worked =
        shift.worked_seconds +
        (shift.is_open && !onBreak ? Math.floor((now - loadedAt) / 1000) : 0);

    return (
        <li className="flex flex-wrap items-center gap-x-4 gap-y-1 px-3 py-2 text-sm">
            <span className="tabular-nums">
                {formatClockTime(shift.clocked_in_at, timezone)} –{' '}
                {shift.clocked_out_at
                    ? formatClockTime(shift.clocked_out_at, timezone)
                    : 'now'}
            </span>
            {shift.is_open && (
                <StatusPill tone={onBreak ? 'warning' : 'success'}>
                    {onBreak
                        ? `On a break for ${formatDuration(secondsSince(shift.on_break_since ?? '', now))}`
                        : 'Clocked in'}
                </StatusPill>
            )}
            {shift.breaks.length > 0 && (
                <span
                    className="text-muted-foreground"
                    title={shift.breaks
                        .map(
                            (item) =>
                                `${formatClockTime(item.started_at, timezone)} – ${item.ended_at ? formatClockTime(item.ended_at, timezone) : 'now'}`,
                        )
                        .join(', ')}
                >
                    {formatDuration(shift.break_seconds)} break
                    {shift.breaks.length > 1 ? ` (${shift.breaks.length})` : ''}
                </span>
            )}
            {shift.note && (
                <span className="min-w-0 truncate text-muted-foreground italic">
                    {shift.note}
                </span>
            )}
            <span className="ml-auto flex items-center gap-1">
                <span className="w-16 text-right font-medium tabular-nums">
                    {formatDuration(worked)}
                </span>
                {canManage && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="size-7"
                                aria-label="Shift actions"
                            >
                                <MoreHorizontal />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem onSelect={onEdit}>
                                <Pencil />
                                Correct
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                variant="destructive"
                                onSelect={onDelete}
                            >
                                <Trash2 />
                                Remove
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}
            </span>
        </li>
    );
}

/**
 * A member's week of shifts, Monday to Sunday, with the time worked each day.
 * Owners and admins can pick anyone and add, correct or remove shifts.
 */
export default function AttendanceTime({
    member,
    isYou,
    week,
    members,
    canManage,
}: {
    member: AttendanceMember;
    isYou: boolean;
    week: AttendanceWeek;
    members: AttendanceMember[];
    canManage: boolean;
}) {
    const running = week.days.some((day) =>
        day.shifts.some((shift) => shift.is_open),
    );
    const now = useNow(running);
    const [loadedAt, setLoadedAt] = useState(() => Date.now());
    const [shownWeek, setShownWeek] = useState(week);
    const [editing, setEditing] = useState<Editing>(null);
    const [deleting, setDeleting] = useState<ShiftSummary | null>(null);

    // Hours were counted when the page loaded; tick on from then.
    if (shownWeek !== week) {
        setShownWeek(week);
        setLoadedAt(Date.now());
    }

    const weekHref = (startsOn: string) =>
        time({
            query: {
                ...(isYou ? {} : { member: member.id }),
                week: startsOn,
            },
        });

    return (
        <>
            <Head title={isYou ? 'My time' : `${member.name}’s time`} />

            <div className="mx-auto flex w-full max-w-4xl flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title={isYou ? 'My time' : `${member.name}’s time`}
                    description={`${formatDay(week.starts_on)} – ${formatDay(week.ends_on)} · ${formatDuration(week.worked_seconds)} worked${week.break_seconds > 0 ? ` · ${formatDuration(week.break_seconds)} on breaks` : ''}`}
                    actions={
                        canManage && members.length > 0 ? (
                            <NativeSelect
                                aria-label="Whose time"
                                value={member.id}
                                onChange={(event) =>
                                    router.get(
                                        time.url({
                                            query: {
                                                member: event.target.value,
                                                week: week.starts_on,
                                            },
                                        }),
                                    )
                                }
                                className="w-48"
                            >
                                {members.map((option) => (
                                    <option key={option.id} value={option.id}>
                                        {option.name}
                                    </option>
                                ))}
                            </NativeSelect>
                        ) : undefined
                    }
                />

                <AttendanceTabs current={isYou ? 'time' : null} />

                <div className="flex items-center gap-1">
                    <Button
                        variant="outline"
                        size="icon"
                        className="size-8"
                        asChild
                    >
                        <Link
                            href={weekHref(addDays(week.starts_on, -7))}
                            aria-label="Previous week"
                            preserveScroll
                        >
                            <ChevronLeft />
                        </Link>
                    </Button>
                    <Button variant="outline" size="sm" asChild>
                        <Link
                            href={time({
                                query: isYou ? {} : { member: member.id },
                            })}
                            preserveScroll
                        >
                            This week
                        </Link>
                    </Button>
                    <Button
                        variant="outline"
                        size="icon"
                        className="size-8"
                        asChild
                    >
                        <Link
                            href={weekHref(addDays(week.starts_on, 7))}
                            aria-label="Next week"
                            preserveScroll
                        >
                            <ChevronRight />
                        </Link>
                    </Button>
                    {!isYou && (
                        <span className="ml-2 text-sm text-muted-foreground">
                            Times in {member.timezone}
                        </span>
                    )}
                </div>

                <ol className="flex flex-col gap-2">
                    {week.days.map((day) => {
                        const date = parseDay(day.date);

                        return (
                            <li
                                key={day.date}
                                className="overflow-hidden rounded-lg border bg-card shadow-xs"
                            >
                                <div
                                    className={cn(
                                        'flex items-center gap-3 px-3 py-2 text-sm',
                                        day.shifts.length > 0 &&
                                            'border-b bg-muted/40',
                                    )}
                                >
                                    <span className="w-10 font-medium">
                                        {weekday.format(date)}
                                    </span>
                                    <span className="text-muted-foreground">
                                        {dayOfMonth.format(date)}
                                    </span>
                                    {day.shifts.length === 0 && (
                                        <span className="text-muted-foreground">
                                            · No shifts
                                        </span>
                                    )}
                                    <span className="ml-auto flex items-center gap-1">
                                        {day.shifts.length > 0 && (
                                            <span className="w-16 text-right font-medium tabular-nums">
                                                {formatDuration(
                                                    day.worked_seconds,
                                                )}
                                            </span>
                                        )}
                                        {canManage && (
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-7"
                                                aria-label={`Add a shift on ${formatDay(day.date)}`}
                                                onClick={() =>
                                                    setEditing({
                                                        date: day.date,
                                                    })
                                                }
                                            >
                                                <Plus />
                                            </Button>
                                        )}
                                    </span>
                                </div>
                                {day.shifts.length > 0 && (
                                    <ul className="divide-y">
                                        {day.shifts.map((shift) => (
                                            <ShiftRow
                                                key={shift.id}
                                                shift={shift}
                                                timezone={member.timezone}
                                                now={now}
                                                loadedAt={loadedAt}
                                                canManage={canManage}
                                                onEdit={() =>
                                                    setEditing({
                                                        shift,
                                                        date: day.date,
                                                    })
                                                }
                                                onDelete={() =>
                                                    setDeleting(shift)
                                                }
                                            />
                                        ))}
                                    </ul>
                                )}
                            </li>
                        );
                    })}
                </ol>
            </div>

            {editing && (
                <ShiftDialog
                    key={editing.shift?.id ?? editing.date}
                    open
                    onOpenChange={(open) => !open && setEditing(null)}
                    member={member}
                    shift={editing.shift}
                    defaultDate={editing.date}
                />
            )}

            {deleting && (
                <ConfirmDelete
                    action={destroy.form(deleting.id)}
                    title="Remove this shift?"
                    description={`${formatClockTime(deleting.clocked_in_at, member.timezone)} on ${formatDay(deleting.work_date)}, with its breaks. The audit log keeps a record of it.`}
                    trigger={null}
                    confirmLabel="Remove shift"
                    open
                    onOpenChange={(open) => !open && setDeleting(null)}
                />
            )}
        </>
    );
}

AttendanceTime.layout = {
    breadcrumbs: [
        { title: 'Attendance', href: attendanceIndex() },
        { title: 'Time', href: time() },
    ],
};
