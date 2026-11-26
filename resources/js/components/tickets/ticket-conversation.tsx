import { router, usePage, usePoll } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { KeyboardEvent, ReactNode } from 'react';
import { toast } from 'sonner';
import LinkedText from '@/components/linked-text';
import SoftBadge from '@/components/soft-badge';
import { AssigneeAvatar } from '@/components/tickets/ticket-meta';
import { Button } from '@/components/ui/button';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { timeAgo } from '@/lib/format';
import { isLive } from '@/lib/live';
import { cn } from '@/lib/utils';
import { destroy as destroyComment } from '@/routes/comments';
import type {
    ActivityItem,
    CustomerOption,
    TicketComment,
    TicketReply,
} from '@/types';

type Tab = 'comments' | 'activity';

/**
 * A ticket's comments, with a composer that can @mention teammates, and its
 * history of changes.
 */
export default function TicketConversation({
    storeUrl,
    comments,
    activity,
    members,
    reload,
    reply,
}: {
    /** Where a new comment is posted: the ticket's or record's comments. */
    storeUrl: string;
    comments: TicketComment[];
    activity: ActivityItem[];
    members: CustomerOption[];
    reload: string[];
    /** Who a reply to the customer goes to and where from, on a ticket. */
    reply?: TicketReply;
}) {
    const [tab, setTab] = useState<Tab>('comments');

    // A reply being emailed shows as "Sending" until the page hears how it
    // went: live updates tell it, otherwise it checks every few seconds.
    const isSending = comments.some(
        (comment) => comment.delivery?.status === 'pending',
    );
    const { start, stop } = usePoll(
        3000,
        { only: reload },
        { autoStart: false },
    );

    useEffect(() => {
        if (!isSending || isLive) {
            return;
        }

        start();

        return stop;
    }, [isSending, start, stop]);

    return (
        <section className="space-y-3">
            <div className="flex gap-4 border-b text-sm">
                {(['comments', 'activity'] as const).map((item) => (
                    <button
                        key={item}
                        type="button"
                        onClick={() => setTab(item)}
                        className={cn(
                            '-mb-px border-b-2 pb-2 capitalize',
                            tab === item
                                ? 'border-foreground font-medium'
                                : 'border-transparent text-muted-foreground hover:text-foreground',
                        )}
                    >
                        {item}
                        {item === 'comments' && comments.length > 0 && (
                            <span className="ml-1.5 text-xs text-subtle-foreground">
                                {comments.length}
                            </span>
                        )}
                    </button>
                ))}
            </div>

            {tab === 'comments' ? (
                <>
                    {comments.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            No comments yet. Start the conversation below.
                        </p>
                    )}
                    <ol className="space-y-4">
                        {comments.map((comment) => (
                            <li key={comment.id} className="group flex gap-2.5">
                                <AssigneeAvatar
                                    name={comment.author_name}
                                    className="mt-0.5 size-6 text-[10px]"
                                />
                                <div className="min-w-0 flex-1">
                                    <p className="flex items-baseline gap-2 text-sm">
                                        <span className="font-medium">
                                            {comment.author_name ?? 'Someone'}
                                        </span>
                                        {comment.sender_email && (
                                            <span title={comment.sender_email}>
                                                <SoftBadge tone="info">
                                                    {comment.from_portal
                                                        ? 'Customer · portal'
                                                        : 'Customer'}
                                                </SoftBadge>
                                            </span>
                                        )}
                                        <Delivery comment={comment} />
                                        <span className="text-xs text-subtle-foreground">
                                            {timeAgo(comment.created_at)}
                                        </span>
                                        {comment.can_delete && (
                                            <button
                                                type="button"
                                                aria-label="Delete comment"
                                                onClick={() =>
                                                    router.delete(
                                                        destroyComment.url(
                                                            comment.id,
                                                        ),
                                                        {
                                                            only: reload,
                                                            preserveScroll: true,
                                                            preserveState: true,
                                                        },
                                                    )
                                                }
                                                className="ml-auto text-subtle-foreground opacity-0 group-hover:opacity-100 hover:text-destructive focus-visible:opacity-100"
                                            >
                                                <Trash2 className="size-3.5" />
                                            </button>
                                        )}
                                    </p>
                                    <p className="mt-0.5 text-sm break-words whitespace-pre-line">
                                        <CommentBody
                                            comment={comment}
                                            members={members}
                                        />
                                    </p>
                                </div>
                            </li>
                        ))}
                    </ol>
                    <Composer
                        storeUrl={storeUrl}
                        members={members}
                        reload={reload}
                        reply={reply}
                    />
                </>
            ) : activity.length === 0 ? (
                <p className="text-sm text-muted-foreground">No changes yet.</p>
            ) : (
                <ol className="space-y-2.5">
                    {activity.map((item) => (
                        <li
                            key={item.id}
                            className="flex items-baseline gap-2 text-sm"
                        >
                            <span className="size-1.5 shrink-0 translate-y-[-2px] rounded-full bg-border-strong" />
                            <span>
                                <span className="font-medium">
                                    {item.actor ?? 'Someone'}
                                </span>{' '}
                                <span className="text-muted-foreground">
                                    {describe(item)}
                                </span>
                            </span>
                            <span className="ml-auto shrink-0 text-xs text-subtle-foreground">
                                {timeAgo(item.created_at)}
                            </span>
                        </li>
                    ))}
                </ol>
            )}
        </section>
    );
}

/**
 * One activity row as a short sentence, e.g. "moved it from New to In progress".
 */
export function describe(item: ActivityItem): string {
    const details = item.details ?? {};
    const text = (value: unknown, fallback: string): string =>
        typeof value === 'string' && value !== '' ? value : fallback;

    switch (item.type) {
        case 'ticket_status_changed':
        case 'project_status_changed':
            return `changed the status from ${text(details.from, '?')} to ${text(details.to, '?')}`;
        case 'ticket_assigned':
            return `assigned it to ${text(details.assignee, 'someone')}`;
        case 'record_updated':
            return Array.isArray(details.fields) && details.fields.length > 0
                ? `changed ${details.fields.join(', ')}`
                : 'changed it';
        case 'record_created':
            return 'added it';
        case 'record_commented':
            return 'commented';
        case 'attachment_added':
            return `attached ${text(details.file, 'a file')}`;
        default:
            return item.label.charAt(0).toLowerCase() + item.label.slice(1);
    }
}

function CommentBody({
    comment,
    members,
}: {
    comment: TicketComment;
    members: CustomerOption[];
}) {
    const names = members
        .filter((member) => comment.mentions.includes(member.id))
        .map((member) => member.name);

    if (names.length === 0) {
        return <LinkedText text={comment.body} />;
    }

    const pattern = new RegExp(
        `(${names.map((name) => `@${name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}`).join('|')})`,
        'g',
    );

    return (
        <>
            {comment.body.split(pattern).map((part, index) =>
                part.startsWith('@') && names.includes(part.slice(1)) ? (
                    <span
                        key={index}
                        className="rounded bg-primary/10 px-0.5 font-medium text-primary"
                    >
                        {part}
                    </span>
                ) : (
                    <LinkedText key={index} text={part} />
                ),
            )}
        </>
    );
}

/**
 * How a reply emailed to the customer fared; nothing for internal notes and
 * for what the customer wrote.
 */
function Delivery({ comment }: { comment: TicketComment }) {
    if (comment.visibility !== 'public' || comment.sender_email) {
        return null;
    }

    if (comment.delivery?.status === 'sent') {
        return <SoftBadge tone="success">Sent to customer</SoftBadge>;
    }

    if (comment.delivery?.status === 'portal') {
        return <SoftBadge tone="success">In the portal</SoftBadge>;
    }

    if (comment.delivery?.status === 'pending') {
        return <SoftBadge tone="neutral">Sending</SoftBadge>;
    }

    return (
        <span title={comment.delivery?.error ?? undefined}>
            <SoftBadge tone="destructive">Not sent</SoftBadge>
        </span>
    );
}

function Composer({
    storeUrl,
    members,
    reload,
    reply,
}: {
    storeUrl: string;
    members: CustomerOption[];
    reload: string[];
    reply?: TicketReply;
}) {
    const { auth } = usePage().props;
    const canReply = Boolean(reply?.to && reply.from);
    const [visibility, setVisibility] =
        useState<TicketComment['visibility']>('internal');
    const isReply = canReply && visibility === 'public';
    const [body, setBody] = useState('');
    const [mentioned, setMentioned] = useState<CustomerOption[]>([]);
    const [query, setQuery] = useState<string | null>(null);
    const [active, setActive] = useState(0);
    const [sending, setSending] = useState(false);
    const textarea = useRef<HTMLTextAreaElement>(null);

    const suggestions =
        query === null
            ? []
            : members
                  .filter((member) => member.id !== auth.user?.id)
                  .filter((member) =>
                      member.name.toLowerCase().includes(query.toLowerCase()),
                  )
                  .slice(0, 6);

    const onChange = (value: string, caret: number) => {
        setBody(value);
        const match = /(?:^|\s)@([^\s@]*)$/.exec(value.slice(0, caret));
        setQuery(match ? match[1] : null);
        setActive(0);
    };

    const pick = (member: CustomerOption) => {
        const element = textarea.current;
        const caret = element?.selectionStart ?? body.length;
        const before = body
            .slice(0, caret)
            .replace(/@([^\s@]*)$/, `@${member.name} `);
        const next = before + body.slice(caret);

        setBody(next);
        setMentioned((current) =>
            current.some((item) => item.id === member.id)
                ? current
                : [...current, member],
        );
        setQuery(null);
        requestAnimationFrame(() => {
            element?.focus();
            element?.setSelectionRange(before.length, before.length);
        });
    };

    const send = () => {
        const text = body.trim();

        if (!text || sending) {
            return;
        }

        router.post(
            storeUrl,
            {
                body: text,
                mentions: mentioned
                    .filter((member) => text.includes(`@${member.name}`))
                    .map((member) => member.id),
                visibility: isReply ? 'public' : 'internal',
            },
            {
                only: reload,
                preserveScroll: true,
                preserveState: true,
                onStart: () => setSending(true),
                onFinish: () => setSending(false),
                onSuccess: () => {
                    setBody('');
                    setMentioned([]);
                },
                onError: (errors) =>
                    toast.error(
                        Object.values(errors)[0] ??
                            'Could not post the comment.',
                    ),
            },
        );
    };

    const onKeyDown = (event: KeyboardEvent<HTMLTextAreaElement>) => {
        if (suggestions.length > 0) {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                setActive(
                    (index) =>
                        (index +
                            (event.key === 'ArrowDown' ? 1 : -1) +
                            suggestions.length) %
                        suggestions.length,
                );

                return;
            }

            if (event.key === 'Enter' || event.key === 'Tab') {
                event.preventDefault();
                pick(suggestions[active]);

                return;
            }

            if (event.key === 'Escape') {
                event.stopPropagation();
                setQuery(null);

                return;
            }
        }

        if (event.key === 'Enter' && (event.metaKey || event.ctrlKey)) {
            event.preventDefault();
            send();
        }
    };

    return (
        <div className="relative rounded-lg border bg-card shadow-xs focus-within:border-input focus-within:ring-[3px] focus-within:ring-ring/30">
            <textarea
                ref={textarea}
                value={body}
                rows={3}
                aria-label="Write a comment"
                placeholder="Write a comment… use @ to mention someone"
                onChange={(event) =>
                    onChange(event.target.value, event.target.selectionStart)
                }
                onKeyDown={onKeyDown}
                className="block w-full resize-none bg-transparent px-3 py-2 text-sm outline-none placeholder:text-subtle-foreground"
            />
            {suggestions.length > 0 && (
                <ul
                    role="listbox"
                    aria-label="Mention someone"
                    className="absolute bottom-full left-2 z-10 mb-1 w-56 rounded-md border bg-popover p-1 shadow-pop"
                >
                    {suggestions.map((member, index) => (
                        <li key={member.id}>
                            <button
                                type="button"
                                role="option"
                                aria-selected={index === active}
                                onMouseDown={(event) => {
                                    event.preventDefault();
                                    pick(member);
                                }}
                                className={cn(
                                    'flex w-full items-center gap-2 rounded px-2 py-1.5 text-left text-sm',
                                    index === active && 'bg-accent',
                                )}
                            >
                                <AssigneeAvatar name={member.name} />
                                {member.name}
                            </button>
                        </li>
                    ))}
                </ul>
            )}
            <div className="flex flex-wrap items-center justify-between gap-2 border-t px-2 py-1.5">
                {canReply ? (
                    <div className="flex min-w-0 flex-wrap items-center gap-2">
                        <ToggleGroup
                            type="single"
                            variant="segmented"
                            value={visibility}
                            onValueChange={(value) =>
                                value &&
                                setVisibility(
                                    value as TicketComment['visibility'],
                                )
                            }
                            aria-label="Who sees this"
                        >
                            <ToggleGroupItem value="internal">
                                Internal note
                            </ToggleGroupItem>
                            <ToggleGroupItem value="public">
                                Reply to customer
                            </ToggleGroupItem>
                        </ToggleGroup>
                        <Hint>
                            {isReply
                                ? `Emailed to ${reply?.to} from ${reply?.from}`
                                : 'Only your team sees this'}
                        </Hint>
                    </div>
                ) : (
                    <Hint>⌘ Enter to send</Hint>
                )}
                <Button
                    size="sm"
                    className="h-7"
                    disabled={!body.trim() || sending}
                    onClick={send}
                >
                    {isReply ? 'Send reply' : 'Comment'}
                </Button>
            </div>
        </div>
    );
}

function Hint({ children }: { children: ReactNode }) {
    return (
        <span className="px-1 text-xs text-subtle-foreground">{children}</span>
    );
}
