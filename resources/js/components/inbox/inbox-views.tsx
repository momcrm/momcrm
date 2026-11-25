import { Link, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { inbox } from '@/routes';
import { index as messagesIndex } from '@/routes/messages';

/**
 * Switches the inbox between notifications and direct messages, with how
 * many of each are unread.
 */
export default function InboxViews({
    current,
}: {
    current: 'notifications' | 'messages';
}) {
    const { sidebarCounts } = usePage().props;
    const views = [
        {
            value: 'notifications',
            label: 'Notifications',
            href: inbox(),
            unread: sidebarCounts?.unreadNotifications ?? 0,
        },
        {
            value: 'messages',
            label: 'Messages',
            href: messagesIndex(),
            unread: sidebarCounts?.unreadMessages ?? 0,
        },
    ] as const;

    return (
        <nav
            aria-label="Inbox"
            className="mx-4 mb-2 grid grid-cols-2 rounded-lg bg-muted p-0.5 text-sm"
        >
            {views.map((view) => (
                <Link
                    key={view.value}
                    href={view.href}
                    aria-current={current === view.value ? 'page' : undefined}
                    className={cn(
                        'flex items-center justify-center gap-1.5 rounded-md px-2 py-1',
                        current === view.value
                            ? 'bg-card font-medium shadow-xs'
                            : 'text-muted-foreground hover:text-foreground',
                    )}
                >
                    {view.label}
                    {view.unread > 0 && (
                        <span className="rounded-full bg-primary/10 px-1.5 text-[11px] text-primary tabular-nums">
                            {view.unread}
                        </span>
                    )}
                </Link>
            ))}
        </nav>
    );
}
