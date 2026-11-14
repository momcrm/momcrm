import { Head, Link, router } from '@inertiajs/react';
import { CheckCheck, Maximize2 } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import NotificationLine from '@/components/inbox/notification-line';
import TicketConversation from '@/components/tickets/ticket-conversation';
import {
    DueChip,
    ProjectTag,
    TicketStatusPill,
} from '@/components/tickets/ticket-meta';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { visitWithQuery } from '@/lib/query';
import { cn } from '@/lib/utils';
import { inbox } from '@/routes';
import { read, readAll } from '@/routes/notifications';
import { index as ticketsIndex } from '@/routes/tickets';
import type {
    InboxKind,
    NotificationItem,
    TicketDrawerData,
    TicketPickerOptions,
} from '@/types';

const tabs: { value: InboxKind | null; label: string; count: string }[] = [
    { value: null, label: 'All', count: 'all' },
    { value: 'mention', label: 'Mentions', count: 'mention' },
    { value: 'assignment', label: 'Assignments', count: 'assignment' },
    { value: 'comment', label: 'Comments', count: 'comment' },
    { value: 'update', label: 'Updates', count: 'update' },
];

const RELOAD = ['notifications', 'counts', 'selected', 'sidebarCounts'];

export default function Inbox({
    notifications,
    counts,
    filters,
    options,
    selected,
}: {
    notifications: NotificationItem[];
    counts: Record<string, number>;
    filters: { kind: InboxKind | null; unread: boolean };
    options: TicketPickerOptions;
    selected: TicketDrawerData | null;
}) {
    const open = (notification: NotificationItem) => {
        const go = () =>
            notification.subject_type === 'project'
                ? router.visit(notification.url)
                : visitWithQuery(
                      { ticket: notification.subject_id },
                      { only: RELOAD },
                  );

        if (notification.read_at === null) {
            router.post(
                read.url(notification.id),
                {},
                {
                    only: ['counts'],
                    preserveScroll: true,
                    preserveState: true,
                    onFinish: go,
                },
            );
        } else {
            go();
        }
    };

    return (
        <>
            <Head title="Inbox" />

            <div className="sticky top-[46px] flex h-[calc(100svh-46px)] min-h-0">
                <aside className="flex w-full max-w-md shrink-0 flex-col border-r">
                    <div className="flex items-center gap-2 px-4 pt-4 pb-2">
                        <h1 className="text-lg font-semibold">Inbox</h1>
                        <label className="ml-auto flex items-center gap-2 text-xs text-muted-foreground">
                            <Checkbox
                                checked={filters.unread}
                                onCheckedChange={(checked) =>
                                    visitWithQuery(
                                        {
                                            unread:
                                                checked === true ? '1' : null,
                                        },
                                        { only: RELOAD },
                                    )
                                }
                                aria-label="Only unread"
                            />
                            Unread only
                        </label>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-7"
                            aria-label="Mark all read"
                            disabled={counts.all === 0}
                            onClick={() =>
                                router.post(
                                    readAll.url(),
                                    {},
                                    {
                                        only: RELOAD,
                                        preserveScroll: true,
                                        preserveState: true,
                                    },
                                )
                            }
                        >
                            <CheckCheck />
                        </Button>
                    </div>
                    <nav
                        aria-label="Kinds"
                        className="flex gap-3 overflow-x-auto border-b px-4 text-sm"
                    >
                        {tabs.map((tab) => (
                            <button
                                key={tab.label}
                                type="button"
                                onClick={() =>
                                    visitWithQuery(
                                        { kind: tab.value },
                                        { only: RELOAD },
                                    )
                                }
                                className={cn(
                                    '-mb-px flex shrink-0 items-center gap-1.5 border-b-2 py-2',
                                    filters.kind === tab.value
                                        ? 'border-foreground font-medium'
                                        : 'border-transparent text-muted-foreground hover:text-foreground',
                                )}
                            >
                                {tab.label}
                                {(counts[tab.count] ?? 0) > 0 && (
                                    <span className="rounded-full bg-primary/10 px-1.5 text-[11px] text-primary tabular-nums">
                                        {counts[tab.count]}
                                    </span>
                                )}
                            </button>
                        ))}
                    </nav>
                    <ul className="flex-1 divide-y overflow-y-auto">
                        {notifications.length === 0 && (
                            <li className="px-4 py-12 text-center text-sm text-muted-foreground">
                                {filters.unread
                                    ? 'No unread notifications.'
                                    : 'Nothing here yet.'}
                            </li>
                        )}
                        {notifications.map((notification) => (
                            <li key={notification.id}>
                                <button
                                    type="button"
                                    onClick={() => open(notification)}
                                    className={cn(
                                        'flex w-full px-4 py-3 text-left hover:bg-accent/50',
                                        selected?.id ===
                                            notification.subject_id &&
                                            'bg-accent/60',
                                    )}
                                >
                                    <NotificationLine
                                        notification={notification}
                                        compact
                                    />
                                </button>
                            </li>
                        ))}
                    </ul>
                </aside>

                <section className="hidden min-w-0 flex-1 overflow-y-auto md:block">
                    {selected ? (
                        <div className="mx-auto max-w-2xl space-y-5 p-6">
                            <header className="space-y-2">
                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <span className="font-mono text-xs">
                                        #{selected.number}
                                    </span>
                                    <ProjectTag
                                        name={selected.project_name}
                                        color={selected.project_color}
                                    />
                                    {!selected.project_name &&
                                        selected.customer_name}
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className="ml-auto h-7"
                                        asChild
                                    >
                                        <Link
                                            href={ticketsIndex({
                                                query: { ticket: selected.id },
                                            })}
                                        >
                                            <Maximize2 />
                                            Open ticket
                                        </Link>
                                    </Button>
                                </div>
                                <h2 className="text-xl font-semibold">
                                    {selected.title}
                                </h2>
                                <div className="flex items-center gap-3">
                                    <TicketStatusPill
                                        status={selected.status}
                                    />
                                    <DueChip
                                        dueOn={selected.due_on}
                                        isClosed={selected.status === 'closed'}
                                    />
                                </div>
                                {selected.description && (
                                    <p className="pt-1 text-sm whitespace-pre-line text-muted-foreground">
                                        {selected.description}
                                    </p>
                                )}
                            </header>
                            <TicketConversation
                                ticketId={selected.id}
                                comments={selected.comments}
                                activity={selected.activity}
                                members={options.members}
                                reload={['selected']}
                            />
                        </div>
                    ) : (
                        <div className="flex h-full items-center justify-center p-6">
                            <EmptyState
                                title="Pick a notification"
                                description="The conversation on its ticket opens here, so you can reply straight away."
                            />
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

Inbox.layout = {
    breadcrumbs: [{ title: 'Inbox', href: inbox() }],
};
