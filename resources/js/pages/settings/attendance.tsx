import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { MoreHorizontal, Pencil, Plus, Star, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import WorkScheduleDialog from '@/components/attendance/work-schedule-dialog';
import ConfirmDelete from '@/components/confirm-delete';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import NativeSelect from '@/components/native-select';
import StatusPill from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { describeWeek } from '@/lib/attendance';
import { formatDay, today } from '@/lib/dates';
import { cn } from '@/lib/utils';
import {
    destroy as destroyHoliday,
    store as storeHoliday,
} from '@/routes/holidays';
import {
    assign,
    destroy as destroySchedule,
    makeDefault,
} from '@/routes/work-schedules';
import { settings as timesheetSettings } from '@/routes/timesheets';
import { edit as editWorkflow } from '@/routes/workflow';
import type { HolidaySummary, WorkScheduleSummary } from '@/types';

type Member = { id: string; name: string; work_schedule_id: string | null };

/**
 * The team's schedules, as rows with what they expect and who works to them.
 */
function Schedules({
    schedules,
    members,
}: {
    schedules: WorkScheduleSummary[];
    members: Member[];
}) {
    const [editing, setEditing] = useState<WorkScheduleSummary | 'new' | null>(
        null,
    );
    const [deleting, setDeleting] = useState<WorkScheduleSummary | null>(null);
    const fallback = schedules.find((schedule) => schedule.is_default);
    const memberCount = (schedule: WorkScheduleSummary) =>
        members.filter((member) =>
            member.work_schedule_id === null
                ? schedule.is_default
                : member.work_schedule_id === schedule.id,
        ).length;

    return (
        <section className="space-y-4">
            <div className="flex items-start justify-between gap-4">
                <Heading
                    variant="small"
                    title="Work schedules"
                    description="The hours people are expected to work. Days are measured against them to show who is late, absent or doing overtime. Everyone works to the default unless you choose another for them."
                />
                <Button
                    size="sm"
                    className="shrink-0"
                    onClick={() => setEditing('new')}
                >
                    <Plus />
                    Add schedule
                </Button>
            </div>

            {schedules.length === 0 ? (
                <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                    No schedules yet. Until you add one, attendance shows hours
                    worked but nobody is late, absent or on overtime.
                </p>
            ) : (
                <ul className="divide-y rounded-lg border bg-card shadow-xs">
                    {schedules.map((schedule) => {
                        const count = memberCount(schedule);
                        const canDelete =
                            !schedule.is_default || schedules.length === 1;

                        return (
                            <li
                                key={schedule.id}
                                className="flex items-center gap-2 px-3 py-2"
                            >
                                <div className="min-w-0 flex-1">
                                    <p className="flex items-center gap-2 truncate text-sm font-medium">
                                        {schedule.name}
                                        {schedule.is_default && (
                                            <StatusPill tone="primary">
                                                Default
                                            </StatusPill>
                                        )}
                                    </p>
                                    <p className="truncate text-xs text-muted-foreground">
                                        {[
                                            describeWeek(schedule.days),
                                            `${schedule.grace_minutes}m grace`,
                                            `${count} ${count === 1 ? 'person' : 'people'}`,
                                        ].join(' · ')}
                                    </p>
                                </div>
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button
                                            variant="ghost"
                                            size="icon-sm"
                                            aria-label={`${schedule.name} actions`}
                                        >
                                            <MoreHorizontal />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem
                                            onSelect={() =>
                                                setEditing(schedule)
                                            }
                                        >
                                            <Pencil />
                                            Edit
                                        </DropdownMenuItem>
                                        {!schedule.is_default && (
                                            <DropdownMenuItem
                                                onSelect={() =>
                                                    router.post(
                                                        makeDefault.url(
                                                            schedule.id,
                                                        ),
                                                        {},
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                <Star />
                                                Make default
                                            </DropdownMenuItem>
                                        )}
                                        <DropdownMenuItem
                                            variant="destructive"
                                            disabled={!canDelete}
                                            title={
                                                canDelete
                                                    ? undefined
                                                    : 'Make another schedule the default first'
                                            }
                                            onSelect={() =>
                                                setDeleting(schedule)
                                            }
                                        >
                                            <Trash2 />
                                            Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </li>
                        );
                    })}
                </ul>
            )}

            {editing && (
                <WorkScheduleDialog
                    key={editing === 'new' ? 'new' : editing.id}
                    open
                    onOpenChange={(open) => !open && setEditing(null)}
                    schedule={editing === 'new' ? undefined : editing}
                />
            )}

            {deleting && (
                <ConfirmDelete
                    action={destroySchedule.form(deleting.id)}
                    title={`Delete ${deleting.name}?`}
                    description={
                        deleting.is_default
                            ? 'Nobody will have hours to be measured against, so no one shows as late, absent or on overtime.'
                            : `Anyone working to it moves to ${fallback?.name ?? 'the default'}. Past days are measured against their new hours.`
                    }
                    trigger={null}
                    open
                    onOpenChange={(open) => !open && setDeleting(null)}
                />
            )}
        </section>
    );
}

/**
 * Which schedule each member works to; changing one saves straight away.
 */
function Assignments({
    schedules,
    members,
}: {
    schedules: WorkScheduleSummary[];
    members: Member[];
}) {
    const fallback = schedules.find((schedule) => schedule.is_default);

    return (
        <section className="space-y-4">
            <Heading
                variant="small"
                title="Who works to which"
                description="Changing someone’s schedule also changes how their past days are measured, except in approved timesheets."
            />
            <ul className="divide-y rounded-lg border bg-card shadow-xs">
                {members.map((member) => (
                    <li
                        key={member.id}
                        className="flex items-center justify-between gap-4 px-3 py-2"
                    >
                        <span className="min-w-0 truncate text-sm font-medium">
                            {member.name}
                        </span>
                        <NativeSelect
                            aria-label={`${member.name}’s schedule`}
                            className="w-56"
                            value={member.work_schedule_id ?? ''}
                            onChange={(event) =>
                                router.put(
                                    assign.url(member.id),
                                    {
                                        work_schedule_id:
                                            event.target.value || null,
                                    },
                                    { preserveScroll: true },
                                )
                            }
                        >
                            <option value="">
                                Default
                                {fallback ? ` (${fallback.name})` : ''}
                            </option>
                            {schedules
                                .filter((schedule) => !schedule.is_default)
                                .map((schedule) => (
                                    <option
                                        key={schedule.id}
                                        value={schedule.id}
                                    >
                                        {schedule.name}
                                    </option>
                                ))}
                        </NativeSelect>
                    </li>
                ))}
            </ul>
        </section>
    );
}

/**
 * The team's holidays: add one by date and name, remove one. Past ones stay
 * listed for a year, faded.
 */
function Holidays({ holidays }: { holidays: HolidaySummary[] }) {
    const todayDate = today();

    return (
        <section className="space-y-4">
            <Heading
                variant="small"
                title="Holidays"
                description="Days nobody is expected to work. Time worked on one counts as overtime."
            />
            <Form
                {...storeHoliday.form()}
                options={{ preserveScroll: true }}
                resetOnSuccess
                className="space-y-1"
            >
                {({ errors, processing }) => (
                    <>
                        <div className="flex gap-2">
                            <Input
                                aria-label="Date"
                                name="date"
                                type="date"
                                className="w-40"
                                required
                            />
                            <Input
                                aria-label="Name"
                                name="name"
                                placeholder="Christmas Day"
                                maxLength={100}
                                required
                            />
                            <Button
                                variant="outline"
                                disabled={processing}
                                className="shrink-0"
                            >
                                <Plus />
                                Add
                            </Button>
                        </div>
                        <InputError message={errors.date ?? errors.name} />
                    </>
                )}
            </Form>
            {holidays.length > 0 && (
                <ul className="divide-y rounded-lg border bg-card shadow-xs">
                    {holidays.map((holiday) => (
                        <li
                            key={holiday.id}
                            className={cn(
                                'flex items-center gap-3 px-3 py-1.5 text-sm',
                                holiday.date < todayDate &&
                                    'text-muted-foreground',
                            )}
                        >
                            <span className="w-32 shrink-0 tabular-nums">
                                {formatDay(holiday.date)}
                            </span>
                            <span className="min-w-0 flex-1 truncate">
                                {holiday.name}
                            </span>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                aria-label={`Remove ${holiday.name}`}
                                onClick={() =>
                                    router.delete(
                                        destroyHoliday.url(holiday.id),
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                <X />
                            </Button>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

/**
 * How long a pay period is: what one timesheet covers. Saves straight away.
 */
function PayPeriods({ period }: { period: 'weekly' | 'fortnightly' }) {
    return (
        <section className="space-y-3">
            <Heading
                variant="small"
                title="Timesheets"
                description="How long each timesheet is. Both start on a Monday; fortnights count from the week you switch."
            />
            <NativeSelect
                aria-label="Pay period"
                className="w-48"
                value={period}
                onChange={(event) =>
                    router.put(
                        timesheetSettings.url(),
                        { timesheet_period: event.target.value },
                        { preserveScroll: true },
                    )
                }
            >
                <option value="weekly">Weekly</option>
                <option value="fortnightly">Fortnightly</option>
            </NativeSelect>
        </section>
    );
}

/**
 * Where the kinds of leave are kept: with the team's other lists.
 */
function LeaveTypes() {
    const types = (usePage().props.choices?.leave_type ?? []).filter(
        (type) => !type.is_archived,
    );

    return (
        <section className="space-y-3">
            <Heading
                variant="small"
                title="Leave types"
                description="The kinds of time off people can ask for. They’re kept with the team’s other lists."
            />
            <p className="text-sm text-muted-foreground">
                {types.length > 0
                    ? types.map((type) => type.name).join(', ')
                    : 'None yet.'}{' '}
                <Link
                    href={editWorkflow({ query: { list: 'leave_type' } })}
                    className="text-foreground underline"
                >
                    {types.length > 0 ? 'Edit leave types' : 'Add leave types'}
                </Link>
            </p>
        </section>
    );
}

export default function AttendanceSettings({
    timesheetPeriod,
    schedules,
    members,
    holidays,
}: {
    timesheetPeriod: 'weekly' | 'fortnightly';
    schedules: WorkScheduleSummary[];
    members: Member[];
    holidays: HolidaySummary[];
}) {
    return (
        <>
            <Head title="Attendance" />

            <h1 className="sr-only">Attendance</h1>

            <Schedules schedules={schedules} members={members} />

            {schedules.length > 1 && (
                <Assignments schedules={schedules} members={members} />
            )}

            <Holidays holidays={holidays} />

            <LeaveTypes />

            <PayPeriods period={timesheetPeriod} />
        </>
    );
}
