import {
    AtSign,
    Bell,
    MessageSquare,
    MessagesSquare,
    UserPlus,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { timeAgo } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { InboxKind, NotificationItem } from '@/types';

export const kindIcons: Record<InboxKind, LucideIcon> = {
    mention: AtSign,
    assignment: UserPlus,
    comment: MessageSquare,
    update: Bell,
    message: MessagesSquare,
};

/**
 * One notification as a sentence: who did what to which ticket, with the
 * comment it came with.
 */
export default function NotificationLine({
    notification,
    compact = false,
}: {
    notification: NotificationItem;
    compact?: boolean;
}) {
    const Icon = kindIcons[notification.kind] ?? Bell;
    const isUnread = notification.read_at === null;

    return (
        <span className="flex min-w-0 flex-1 gap-3">
            <span
                className={cn(
                    'mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full',
                    isUnread
                        ? 'bg-primary/10 text-primary'
                        : 'bg-muted text-subtle-foreground',
                )}
            >
                <Icon className="size-3.5" />
            </span>
            <span className="min-w-0 flex-1">
                <span
                    className={cn(
                        'block truncate text-sm',
                        isUnread && 'font-medium',
                    )}
                >
                    {notification.subject_type === 'ticket' && (
                        <span className="mr-1.5 font-mono text-xs font-normal text-subtle-foreground">
                            #{notification.number}
                        </span>
                    )}
                    {notification.title}
                </span>
                <span className="block truncate text-xs text-muted-foreground">
                    {notification.actor_name
                        ? `${notification.actor_name} ${notification.message}`
                        : notification.message.charAt(0).toUpperCase() +
                          notification.message.slice(1)}
                    {notification.project_name &&
                        notification.subject_type === 'ticket' &&
                        ` · ${notification.project_name}`}
                </span>
                {!compact && notification.excerpt && (
                    <span className="mt-1 line-clamp-2 block rounded-md bg-muted px-2 py-1 text-xs text-muted-foreground">
                        {notification.excerpt}
                    </span>
                )}
            </span>
            <span className="flex shrink-0 flex-col items-end gap-1.5">
                <span className="text-[11px] text-subtle-foreground">
                    {timeAgo(notification.created_at)}
                </span>
                {isUnread && (
                    <span
                        aria-label="Unread"
                        className="size-2 rounded-full bg-primary"
                    />
                )}
            </span>
        </span>
    );
}
