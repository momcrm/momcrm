import { Head, router, usePage, usePoll } from '@inertiajs/react';
import { Check, ChevronLeft, PenSquare, Send, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { KeyboardEvent } from 'react';
import { toast } from 'sonner';
import EmptyState from '@/components/empty-state';
import InboxViews from '@/components/inbox/inbox-views';
import { AssigneeAvatar } from '@/components/tickets/ticket-meta';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { timeAgo } from '@/lib/format';
import { isLive } from '@/lib/live';
import { visitWithQuery } from '@/lib/query';
import { cn } from '@/lib/utils';
import { inbox } from '@/routes';
import { store as startConversation } from '@/routes/conversations';
import {
    index as messagesIndex,
    store as storeMessage,
} from '@/routes/messages';
import type {
    ConversationSummary,
    ConversationThread,
    CustomerOption,
    DirectMessage,
} from '@/types';

const RELOAD = ['conversations', 'selected', 'sidebarCounts'];

/** How often an open inbox checks for new messages without live updates. */
const POLL_MS = 5000;

const names = (people: CustomerOption[]): string =>
    people.length === 0
        ? 'Nobody else'
        : people.map((person) => person.name).join(', ');

/**
 * The inbox's Messages tab: private conversations with teammates on the left,
 * the open one on the right, and a way to start a new one.
 */
export default function Messages({
    conversations,
    selected,
    members,
}: {
    conversations: ConversationSummary[];
    selected: ConversationThread | null;
    members: CustomerOption[];
}) {
    const [isComposing, setIsComposing] = useState(false);
    const isRightPaneOpen = isComposing || selected !== null;

    usePoll(POLL_MS, { only: RELOAD }, { autoStart: !isLive });

    const open = (id: string | null) => {
        setIsComposing(false);
        visitWithQuery({ conversation: id }, { only: RELOAD });
    };

    return (
        <>
            <Head title="Messages" />

            <div className="sticky top-[46px] flex h-[calc(100svh-46px)] min-h-0">
                <aside
                    className={cn(
                        'w-full shrink-0 flex-col border-r md:flex md:max-w-md',
                        isRightPaneOpen ? 'hidden' : 'flex',
                    )}
                >
                    <div className="flex items-center gap-2 px-4 pt-4 pb-2">
                        <h1 className="text-lg font-semibold">Inbox</h1>
                        <Button
                            variant="ghost"
                            size="sm"
                            className="ml-auto h-7"
                            onClick={() => setIsComposing(true)}
                            disabled={members.length === 0}
                        >
                            <PenSquare />
                            New message
                        </Button>
                    </div>
                    <InboxViews current="messages" />
                    <ul className="flex-1 divide-y overflow-y-auto border-t">
                        {conversations.length === 0 && (
                            <li className="px-4 py-12 text-center text-sm text-muted-foreground">
                                {members.length === 0
                                    ? 'Invite teammates to message them here.'
                                    : 'No messages yet.'}
                            </li>
                        )}
                        {conversations.map((conversation) => (
                            <li key={conversation.id}>
                                <button
                                    type="button"
                                    onClick={() => open(conversation.id)}
                                    className={cn(
                                        'flex w-full gap-3 px-4 py-3 text-left hover:bg-accent/50',
                                        !isComposing &&
                                            selected?.id === conversation.id &&
                                            'bg-accent/60',
                                    )}
                                >
                                    <ConversationLine
                                        conversation={conversation}
                                    />
                                </button>
                            </li>
                        ))}
                    </ul>
                </aside>

                <section
                    className={cn(
                        'min-w-0 flex-1 flex-col md:flex',
                        isRightPaneOpen ? 'flex' : 'hidden',
                    )}
                >
                    {isComposing ? (
                        <NewMessage
                            members={members}
                            onClose={() => setIsComposing(false)}
                        />
                    ) : selected ? (
                        <Thread
                            key={selected.id}
                            thread={selected}
                            onBack={() => open(null)}
                        />
                    ) : (
                        <div className="flex h-full items-center justify-center p-6">
                            <EmptyState
                                title="Pick a conversation"
                                description="Or start a new one to message a teammate privately."
                            />
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

function ConversationLine({
    conversation,
}: {
    conversation: ConversationSummary;
}) {
    const { auth } = usePage().props;
    const isUnread = conversation.unread > 0;
    const last = conversation.last_message;

    return (
        <>
            <AssigneeAvatar
                name={conversation.participants[0]?.name ?? null}
                className="mt-0.5 size-7 text-[11px]"
            />
            <span className="min-w-0 flex-1">
                <span
                    className={cn(
                        'block truncate text-sm',
                        isUnread && 'font-medium',
                    )}
                >
                    {names(conversation.participants)}
                </span>
                <span className="block truncate text-xs text-muted-foreground">
                    {last && last.author_id === auth.user?.id && 'You: '}
                    {last?.body}
                </span>
            </span>
            <span className="flex shrink-0 flex-col items-end gap-1.5">
                <span className="text-[11px] text-subtle-foreground">
                    {timeAgo(conversation.last_message_at)}
                </span>
                {isUnread && (
                    <span className="rounded-full bg-primary px-1.5 text-[11px] text-primary-foreground tabular-nums">
                        {conversation.unread}
                    </span>
                )}
            </span>
        </>
    );
}

function Thread({
    thread,
    onBack,
}: {
    thread: ConversationThread;
    onBack: () => void;
}) {
    const end = useRef<HTMLDivElement>(null);
    const count = thread.messages.length;

    useEffect(() => {
        end.current?.scrollIntoView({ block: 'end' });
    }, [count]);

    return (
        <>
            <header className="flex items-center gap-2 border-b px-4 py-3">
                <button
                    type="button"
                    onClick={onBack}
                    aria-label="Back to messages"
                    className="text-muted-foreground hover:text-foreground md:hidden"
                >
                    <ChevronLeft className="size-4" />
                </button>
                <h2 className="truncate font-semibold">
                    {names(thread.participants)}
                </h2>
            </header>
            <div className="flex-1 overflow-y-auto">
                <ol className="mx-auto max-w-2xl space-y-4 p-4 md:p-6">
                    {thread.messages.map((message) => (
                        <MessageItem key={message.id} message={message} />
                    ))}
                </ol>
                <div ref={end} />
            </div>
            <Composer
                placeholder={`Message ${names(thread.participants)}`}
                onSend={(body, done) =>
                    router.post(
                        storeMessage.url(thread.id),
                        { body },
                        {
                            only: RELOAD,
                            preserveScroll: true,
                            preserveState: true,
                            onSuccess: done.success,
                            onFinish: done.finish,
                            onError: (errors) =>
                                toast.error(
                                    Object.values(errors)[0] ??
                                        'Could not send the message.',
                                ),
                        },
                    )
                }
            />
        </>
    );
}

function MessageItem({ message }: { message: DirectMessage }) {
    return (
        <li className="flex gap-2.5">
            <AssigneeAvatar
                name={message.author_name ?? 'Someone'}
                className="mt-0.5 size-6 text-[10px]"
            />
            <div className="min-w-0 flex-1">
                <p className="flex items-baseline gap-2 text-sm">
                    <span className="font-medium">
                        {message.author_name ?? 'Someone'}
                    </span>
                    <span className="text-xs text-subtle-foreground">
                        {timeAgo(message.created_at)}
                    </span>
                </p>
                <p className="text-sm break-words whitespace-pre-line">
                    {message.body}
                </p>
            </div>
        </li>
    );
}

function NewMessage({
    members,
    onClose,
}: {
    members: CustomerOption[];
    onClose: () => void;
}) {
    const [recipients, setRecipients] = useState<CustomerOption[]>([]);
    const [query, setQuery] = useState('');
    const matches = members
        .filter((member) =>
            member.name.toLowerCase().includes(query.trim().toLowerCase()),
        )
        .slice(0, 8);
    const toggle = (member: CustomerOption) =>
        setRecipients((current) =>
            current.some((item) => item.id === member.id)
                ? current.filter((item) => item.id !== member.id)
                : [...current, member],
        );

    return (
        <>
            <header className="flex items-center gap-2 border-b px-4 py-3">
                <h2 className="font-semibold">New message</h2>
                <Button
                    variant="ghost"
                    size="icon"
                    className="ml-auto size-7"
                    aria-label="Cancel"
                    onClick={onClose}
                >
                    <X />
                </Button>
            </header>
            <div className="flex-1 overflow-y-auto">
                <div className="mx-auto max-w-2xl space-y-3 p-4 md:p-6">
                    <div className="flex flex-wrap items-center gap-1.5">
                        <span className="text-sm text-muted-foreground">
                            To
                        </span>
                        {recipients.map((recipient) => (
                            <span
                                key={recipient.id}
                                className="inline-flex items-center gap-1 rounded-full bg-primary/10 py-0.5 pr-1 pl-2 text-sm text-primary"
                            >
                                {recipient.name}
                                <button
                                    type="button"
                                    aria-label={`Remove ${recipient.name}`}
                                    onClick={() => toggle(recipient)}
                                    className="rounded-full p-0.5 hover:bg-primary/10"
                                >
                                    <X className="size-3" />
                                </button>
                            </span>
                        ))}
                    </div>
                    <Input
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder="Find a teammate"
                        aria-label="Find a teammate"
                        autoFocus
                    />
                    <ul className="divide-y rounded-lg border bg-card">
                        {matches.length === 0 && (
                            <li className="px-3 py-2 text-sm text-muted-foreground">
                                Nobody in your team matches.
                            </li>
                        )}
                        {matches.map((member) => {
                            const isChosen = recipients.some(
                                (item) => item.id === member.id,
                            );

                            return (
                                <li key={member.id}>
                                    <button
                                        type="button"
                                        onClick={() => toggle(member)}
                                        aria-pressed={isChosen}
                                        className="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm hover:bg-accent/50"
                                    >
                                        <AssigneeAvatar
                                            name={member.name}
                                            className="size-6 text-[10px]"
                                        />
                                        <span className="flex-1 truncate">
                                            {member.name}
                                        </span>
                                        {isChosen && (
                                            <Check className="size-4 text-primary" />
                                        )}
                                    </button>
                                </li>
                            );
                        })}
                    </ul>
                </div>
            </div>
            <Composer
                placeholder={
                    recipients.length === 0
                        ? 'Choose who to message first'
                        : `Message ${names(recipients)}`
                }
                isDisabled={recipients.length === 0}
                onSend={(body, done) =>
                    router.post(
                        startConversation.url(),
                        {
                            recipients: recipients.map((item) => item.id),
                            body,
                        },
                        {
                            onSuccess: () => {
                                done.success();
                                onClose();
                            },
                            onFinish: done.finish,
                            onError: (errors) =>
                                toast.error(
                                    Object.values(errors)[0] ??
                                        'Could not send the message.',
                                ),
                        },
                    )
                }
            />
        </>
    );
}

/**
 * Where a message is written. Enter sends it; Shift+Enter starts a new line.
 */
function Composer({
    placeholder,
    isDisabled = false,
    onSend,
}: {
    placeholder: string;
    isDisabled?: boolean;
    onSend: (
        body: string,
        done: { success: () => void; finish: () => void },
    ) => void;
}) {
    const [body, setBody] = useState('');
    const [isSending, setIsSending] = useState(false);

    const send = () => {
        const text = body.trim();

        if (!text || isSending || isDisabled) {
            return;
        }

        setIsSending(true);
        onSend(text, {
            success: () => setBody(''),
            finish: () => setIsSending(false),
        });
    };

    const onKeyDown = (event: KeyboardEvent<HTMLTextAreaElement>) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            send();
        }
    };

    return (
        <div className="sticky bottom-0 border-t bg-background p-3">
            <div className="mx-auto flex max-w-2xl items-end gap-2">
                <Textarea
                    value={body}
                    onChange={(event) => setBody(event.target.value)}
                    onKeyDown={onKeyDown}
                    placeholder={placeholder}
                    aria-label="Message"
                    disabled={isDisabled}
                    maxLength={5000}
                    className="max-h-48 min-h-10"
                    rows={1}
                />
                <Button
                    size="icon"
                    aria-label="Send"
                    onClick={send}
                    disabled={isDisabled || isSending || body.trim() === ''}
                >
                    <Send />
                </Button>
            </div>
        </div>
    );
}

Messages.layout = {
    breadcrumbs: [
        { title: 'Inbox', href: inbox() },
        { title: 'Messages', href: messagesIndex() },
    ],
};
