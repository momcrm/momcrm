import { Head, router } from '@inertiajs/react';
import { Check, CheckCheck, Mail, Trash2 } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import NotificationLine from '@/components/inbox/notification-line';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { destroy, index, read, readAll } from '@/routes/notifications';
import type { NotificationItem } from '@/types';

const keep = { preserveScroll: true, preserveState: true };

export default function Notifications({
    notifications,
}: {
    notifications: NotificationItem[];
}) {
    const unread = notifications.filter(
        (notification) => notification.read_at === null,
    ).length;

    const open = (notification: NotificationItem) => {
        if (notification.read_at === null) {
            router.post(
                read.url(notification.id),
                {},
                { ...keep, onFinish: () => router.visit(notification.url) },
            );
        } else {
            router.visit(notification.url);
        }
    };

    return (
        <>
            <Head title="Notifications" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Notifications"
                    description={
                        unread > 0
                            ? `${unread} unread`
                            : 'You are all caught up.'
                    }
                    actions={
                        <Button
                            variant="outline"
                            disabled={unread === 0}
                            onClick={() => router.post(readAll.url(), {}, keep)}
                        >
                            <CheckCheck />
                            Mark all read
                        </Button>
                    }
                />

                {notifications.length === 0 ? (
                    <EmptyState
                        title="No notifications"
                        description="You'll hear here when someone assigns you a ticket, mentions you or comments on what you follow."
                    />
                ) : (
                    <ul className="divide-y overflow-hidden rounded-lg border bg-card shadow-xs">
                        {notifications.map((notification) => (
                            <li
                                key={notification.id}
                                className="group flex items-start gap-1 pr-2 hover:bg-accent/40"
                            >
                                <button
                                    type="button"
                                    onClick={() => open(notification)}
                                    className="flex min-w-0 flex-1 px-4 py-3 text-left"
                                >
                                    <NotificationLine
                                        notification={notification}
                                    />
                                </button>
                                <div className="flex shrink-0 gap-0.5 pt-3 opacity-0 group-hover:opacity-100 focus-within:opacity-100">
                                    <Action
                                        label={
                                            notification.read_at
                                                ? 'Mark unread'
                                                : 'Mark read'
                                        }
                                        onClick={() =>
                                            router.post(
                                                read.url(notification.id),
                                                {
                                                    read:
                                                        notification.read_at ===
                                                        null,
                                                },
                                                keep,
                                            )
                                        }
                                    >
                                        {notification.read_at ? (
                                            <Mail />
                                        ) : (
                                            <Check />
                                        )}
                                    </Action>
                                    <Action
                                        label="Clear"
                                        onClick={() =>
                                            router.delete(
                                                destroy.url(notification.id),
                                                keep,
                                            )
                                        }
                                    >
                                        <Trash2 />
                                    </Action>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

function Action({
    label,
    onClick,
    children,
}: {
    label: string;
    onClick: () => void;
    children: React.ReactNode;
}) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-7"
                    aria-label={label}
                    onClick={onClick}
                >
                    {children}
                </Button>
            </TooltipTrigger>
            <TooltipContent>{label}</TooltipContent>
        </Tooltip>
    );
}

Notifications.layout = {
    breadcrumbs: [{ title: 'Notifications', href: index() }],
};
