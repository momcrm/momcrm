import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { Check, Plus, X } from 'lucide-react';
import { useState } from 'react';
import AttendanceTabs from '@/components/attendance/attendance-tabs';
import LeaveDialog from '@/components/attendance/leave-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import StatusPill from '@/components/status-pill';
import { AssigneeAvatar } from '@/components/tickets/ticket-meta';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import {
    describeLeaveDays,
    formatLeaveDays,
    leaveStatuses,
} from '@/lib/attendance';
import { hueStyle } from '@/lib/colors';
import {
    index as attendanceIndex,
    leave as leaveRoute,
} from '@/routes/attendance';
import { approve, cancel, decline } from '@/routes/leave-requests';
import { edit as editWorkflow } from '@/routes/workflow';
import type { LeaveSummary } from '@/types';

/**
 * One request: its type and days, whose it is, where it stands and any note,
 * with the actions the viewer may take.
 */
function LeaveRow({
    leave,
    showName,
    actions,
}: {
    leave: LeaveSummary;
    showName: boolean;
    actions?: React.ReactNode;
}) {
    const status = leaveStatuses[leave.status];

    return (
        <li className="flex flex-wrap items-center gap-x-3 gap-y-1 px-3 py-2.5 text-sm">
            {showName && (
                <span className="flex w-36 min-w-0 items-center gap-2">
                    <AssigneeAvatar name={leave.user_name} />
                    <span className="truncate font-medium">
                        {leave.user_name}
                    </span>
                </span>
            )}
            <span className="flex min-w-0 flex-1 flex-col">
                <span className="flex min-w-0 items-center gap-2">
                    <span
                        className="size-2 shrink-0 rounded-full hue-dot"
                        style={hueStyle(leave.hue)}
                    />
                    <span className="truncate font-medium">
                        {leave.leave_type}
                    </span>
                    <span className="truncate text-muted-foreground">
                        {describeLeaveDays(
                            leave.starts_on,
                            leave.ends_on,
                            leave.part,
                        )}
                        {leave.days !== null &&
                            ` · ${formatLeaveDays(leave.days)}`}
                    </span>
                </span>
                {(leave.reason || leave.review_note) && (
                    <span className="truncate text-xs text-muted-foreground">
                        {[
                            leave.reason,
                            leave.review_note &&
                                `${leave.reviewed_by_name ?? 'Reply'}: ${leave.review_note}`,
                        ]
                            .filter(Boolean)
                            .join(' · ')}
                    </span>
                )}
            </span>
            <StatusPill tone={status.tone}>{status.label}</StatusPill>
            {actions}
        </li>
    );
}

function CancelButton({ leave }: { leave: LeaveSummary }) {
    return (
        <Button
            variant="ghost"
            size="sm"
            className="h-7"
            onClick={() =>
                router.post(cancel.url(leave.id), {}, { preserveScroll: true })
            }
        >
            Cancel
        </Button>
    );
}

/**
 * Asks why before declining; the note goes to the member.
 */
function DeclineDialog({
    leave,
    onClose,
}: {
    leave: LeaveSummary;
    onClose: () => void;
}) {
    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-md">
                <DialogTitle>Decline {leave.user_name}’s leave?</DialogTitle>
                <DialogDescription>
                    {leave.leave_type},{' '}
                    {describeLeaveDays(
                        leave.starts_on,
                        leave.ends_on,
                        leave.part,
                    )}
                    . They’ll see your note in their inbox.
                </DialogDescription>
                <Form
                    {...decline.form(leave.id)}
                    options={{ preserveScroll: true }}
                    onSuccess={onClose}
                    className="space-y-4"
                >
                    {({ errors, processing }) => (
                        <>
                            <Textarea
                                name="review_note"
                                aria-label="Note"
                                placeholder="Why, or what would work instead (optional)"
                                rows={3}
                                maxLength={500}
                            />
                            <InputError
                                message={errors.review_note ?? errors.leave}
                            />
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={onClose}
                                >
                                    Keep it waiting
                                </Button>
                                <Button
                                    variant="destructive"
                                    disabled={processing}
                                >
                                    Decline
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

/**
 * Leave: your own requests, and for owners and admins the requests waiting
 * for them and who is away soon.
 */
export default function AttendanceLeave({
    today,
    mine,
    pending,
    upcoming,
    members,
    canManage,
}: {
    today: string;
    mine: LeaveSummary[];
    pending: LeaveSummary[];
    upcoming: LeaveSummary[];
    members: { id: string; name: string }[];
    canManage: boolean;
}) {
    const [dialog, setDialog] = useState<'request' | 'record' | null>(null);
    const [declining, setDeclining] = useState<LeaveSummary | null>(null);
    const { errors } = usePage().props;
    const hasTypes = (usePage().props.choices?.leave_type ?? []).some(
        (type) => !type.is_archived,
    );

    return (
        <>
            <Head title="Leave" />

            <div className="mx-auto flex w-full max-w-4xl flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Leave"
                    description="Time off, asked for and approved here. Approved leave shows on the calendar and keeps people from showing as absent."
                />

                <AttendanceTabs
                    current="leave"
                    actions={
                        hasTypes ? (
                            <>
                                {canManage && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setDialog('record')}
                                    >
                                        Record leave
                                    </Button>
                                )}
                                <Button
                                    size="sm"
                                    onClick={() => setDialog('request')}
                                >
                                    <Plus />
                                    Request leave
                                </Button>
                            </>
                        ) : undefined
                    }
                />

                {!hasTypes && (
                    <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                        Your team has no types of leave yet.{' '}
                        {canManage ? (
                            <>
                                Add some, such as annual and sick leave, in{' '}
                                <Link
                                    href={editWorkflow({
                                        query: { list: 'leave_type' },
                                    })}
                                    className="text-foreground underline"
                                >
                                    Settings → Workflow
                                </Link>
                                .
                            </>
                        ) : (
                            'Ask an owner or admin to add some.'
                        )}
                    </p>
                )}

                <InputError message={errors.leave} />

                {canManage && (
                    <section className="space-y-3">
                        <Heading
                            variant="small"
                            title="Waiting for you"
                            description="Approving tells the member and takes the days off their schedule."
                        />
                        {pending.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Nothing waiting.
                            </p>
                        ) : (
                            <ul className="divide-y rounded-lg border bg-card shadow-xs">
                                {pending.map((leave) => (
                                    <LeaveRow
                                        key={leave.id}
                                        leave={leave}
                                        showName
                                        actions={
                                            <span className="flex gap-1">
                                                <Button
                                                    size="sm"
                                                    className="h-7"
                                                    onClick={() =>
                                                        router.post(
                                                            approve.url(
                                                                leave.id,
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
                                                        setDeclining(leave)
                                                    }
                                                >
                                                    <X />
                                                    Decline
                                                </Button>
                                            </span>
                                        }
                                    />
                                ))}
                            </ul>
                        )}
                    </section>
                )}

                {canManage && upcoming.length > 0 && (
                    <section className="space-y-3">
                        <Heading
                            variant="small"
                            title="Coming up"
                            description="Approved leave in the next 90 days."
                        />
                        <ul className="divide-y rounded-lg border bg-card shadow-xs">
                            {upcoming.map((leave) => (
                                <LeaveRow
                                    key={leave.id}
                                    leave={leave}
                                    showName
                                    actions={
                                        leave.can_cancel && (
                                            <CancelButton leave={leave} />
                                        )
                                    }
                                />
                            ))}
                        </ul>
                    </section>
                )}

                <section className="space-y-3">
                    <Heading variant="small" title="Your leave" />
                    {mine.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            You haven’t asked for any leave yet.
                        </p>
                    ) : (
                        <ul className="divide-y rounded-lg border bg-card shadow-xs">
                            {mine.map((leave) => (
                                <LeaveRow
                                    key={leave.id}
                                    leave={leave}
                                    showName={false}
                                    actions={
                                        leave.can_cancel && (
                                            <CancelButton leave={leave} />
                                        )
                                    }
                                />
                            ))}
                        </ul>
                    )}
                </section>
            </div>

            {dialog && (
                <LeaveDialog
                    key={dialog}
                    open
                    onOpenChange={(open) => !open && setDialog(null)}
                    today={today}
                    members={dialog === 'record' ? members : undefined}
                />
            )}

            {declining && (
                <DeclineDialog
                    leave={declining}
                    onClose={() => setDeclining(null)}
                />
            )}
        </>
    );
}

AttendanceLeave.layout = {
    breadcrumbs: [
        { title: 'Attendance', href: attendanceIndex() },
        { title: 'Leave', href: leaveRoute() },
    ],
};
