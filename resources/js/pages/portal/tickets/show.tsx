import { Form, Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, FileText, Image, Paperclip, Upload } from 'lucide-react';
import { useRef, useState } from 'react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
import LinkedText from '@/components/linked-text';
import SoftBadge from '@/components/soft-badge';
import StatusPill from '@/components/status-pill';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useInitials } from '@/hooks/use-initials';
import { formatBytes, timeAgo } from '@/lib/format';
import { cn } from '@/lib/utils';
import StorePortalTicketReplyController from '@/actions/App/Portal/Controllers/StorePortalTicketReplyController';
import { close, reopen } from '@/routes/portal/tickets';
import { store as storeFiles } from '@/routes/portal/tickets/files';
import { index } from '@/routes/tickets';
import type { PortalFile, PortalTicket, PortalTicketMessage } from '@/types';

type Props = {
    ticket: PortalTicket & { description: string | null };
    conversation: PortalTicketMessage[];
    files: PortalFile[];
    can: { reply: boolean; attach: boolean; close: boolean; reopen: boolean };
    accept: string;
};

function Message({
    name,
    body,
    createdAt,
    isCustomer,
}: {
    name: string | null;
    body: string;
    createdAt: string | null;
    isCustomer: boolean;
}) {
    const getInitials = useInitials();

    return (
        <li className="flex gap-3">
            <Avatar className="mt-0.5 size-8">
                <AvatarFallback
                    className={cn(
                        'text-xs',
                        !isCustomer && 'bg-primary/10 text-primary',
                    )}
                >
                    {getInitials(name ?? '?')}
                </AvatarFallback>
            </Avatar>
            <div className="min-w-0 flex-1 rounded-lg border bg-card px-3 py-2.5">
                <p className="flex flex-wrap items-baseline gap-2 text-sm">
                    <span className="font-medium">{name ?? 'Someone'}</span>
                    {!isCustomer && (
                        <SoftBadge tone="primary">Support</SoftBadge>
                    )}
                    <span className="text-xs text-muted-foreground">
                        {timeAgo(createdAt)}
                    </span>
                </p>
                <p className="mt-1 text-sm break-words whitespace-pre-wrap">
                    <LinkedText text={body} />
                </p>
            </div>
        </li>
    );
}

function Files({
    ticketId,
    files,
    canAttach,
    accept,
}: {
    ticketId: string;
    files: PortalFile[];
    canAttach: boolean;
    accept: string;
}) {
    const input = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);

    const upload = (chosen: FileList | null) => {
        if (!chosen || chosen.length === 0) {
            return;
        }

        router.post(
            storeFiles.url(ticketId),
            { files: Array.from(chosen) },
            {
                forceFormData: true,
                preserveScroll: true,
                onStart: () => setUploading(true),
                onFinish: () => setUploading(false),
                onError: (errors) => {
                    const message = Object.values(errors)[0];

                    if (message) {
                        toast.error(message);
                    }
                },
            },
        );
    };

    if (files.length === 0 && !canAttach) {
        return null;
    }

    return (
        <section className="space-y-2">
            <h2 className="text-sm font-medium">Files</h2>
            {files.length > 0 && (
                <ul className="space-y-1">
                    {files.map((file) => {
                        const Icon = file.mime_type.startsWith('image/')
                            ? Image
                            : file.mime_type === 'application/pdf'
                              ? FileText
                              : Paperclip;

                        return (
                            <li
                                key={file.id}
                                className="flex h-10 items-center gap-2.5 rounded-md border bg-card px-2.5 text-sm"
                            >
                                <Icon className="size-4 shrink-0 text-muted-foreground" />
                                <a
                                    href={file.url}
                                    className="min-w-0 flex-1 truncate hover:underline"
                                >
                                    {file.name}
                                </a>
                                <span className="shrink-0 text-xs text-muted-foreground">
                                    {formatBytes(file.size)}
                                </span>
                            </li>
                        );
                    })}
                </ul>
            )}
            {canAttach && (
                <>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={uploading}
                        onClick={() => input.current?.click()}
                    >
                        {uploading ? <Spinner /> : <Upload />}
                        {uploading ? 'Uploading…' : 'Add files'}
                    </Button>
                    <input
                        ref={input}
                        type="file"
                        multiple
                        hidden
                        accept={accept
                            .split(',')
                            .map((extension) => `.${extension}`)
                            .join(',')}
                        onChange={(event) => {
                            upload(event.target.files);
                            event.target.value = '';
                        }}
                    />
                </>
            )}
        </section>
    );
}

/**
 * One of the customer's tickets: what was asked, the conversation with
 * support, shared files, and a reply box.
 */
export default function PortalTicketShow({
    ticket,
    conversation,
    files,
    can,
    accept,
}: Props) {
    return (
        <>
            <Head title={`#${ticket.number} ${ticket.title}`} />

            <div className="space-y-2">
                <Link
                    href={index()}
                    className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="size-4" />
                    Tickets
                </Link>
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="min-w-0 space-y-1">
                        <h1 className="text-xl font-semibold">
                            <span className="text-muted-foreground">
                                #{ticket.number}
                            </span>{' '}
                            {ticket.title}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {ticket.raised_by
                                ? `Raised by ${ticket.raised_by} `
                                : 'Raised '}
                            {timeAgo(ticket.created_at)}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <StatusPill hue={ticket.status.hue}>
                            {ticket.status.name}
                        </StatusPill>
                        {can.close && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    router.post(
                                        close.url(ticket.id),
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                Close ticket
                            </Button>
                        )}
                        {can.reopen && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    router.post(
                                        reopen.url(ticket.id),
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                Reopen
                            </Button>
                        )}
                    </div>
                </div>
            </div>

            <div className="grid gap-6 lg:grid-cols-[1fr_16rem]">
                <section className="space-y-4">
                    <ol className="space-y-4">
                        {ticket.description && (
                            <Message
                                name={ticket.raised_by}
                                body={ticket.description}
                                createdAt={ticket.created_at}
                                isCustomer
                            />
                        )}
                        {conversation.map((message) => (
                            <Message
                                key={message.id}
                                name={message.author_name}
                                body={message.body}
                                createdAt={message.created_at}
                                isCustomer={message.is_customer}
                            />
                        ))}
                    </ol>

                    {!ticket.description && conversation.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            No messages yet.
                        </p>
                    )}

                    {can.reply && (
                        <Form
                            {...StorePortalTicketReplyController.form(
                                ticket.id,
                            )}
                            resetOnSuccess
                            options={{ preserveScroll: true }}
                            className="space-y-2"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <Textarea
                                        name="body"
                                        rows={4}
                                        required
                                        aria-label="Your reply"
                                        placeholder={
                                            ticket.status.is_closed
                                                ? 'Reply to reopen this ticket…'
                                                : 'Write a reply…'
                                        }
                                    />
                                    <InputError message={errors.body} />
                                    <Button disabled={processing}>
                                        {processing && <Spinner />}
                                        Send reply
                                    </Button>
                                </>
                            )}
                        </Form>
                    )}
                </section>

                <aside>
                    <Files
                        ticketId={ticket.id}
                        files={files}
                        canAttach={can.attach}
                        accept={accept}
                    />
                </aside>
            </div>
        </>
    );
}
