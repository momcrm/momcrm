import { router } from '@inertiajs/react';
import { FileText, Image, Paperclip, Trash2, Upload } from 'lucide-react';
import { useRef, useState } from 'react';
import type { DragEvent } from 'react';
import { toast } from 'sonner';
import { formatBytes, timeAgo } from '@/lib/format';
import { cn } from '@/lib/utils';
import { destroy } from '@/routes/attachments';
import type { Abilities, AttachmentItem } from '@/types';

/**
 * A ticket's or project's files, with a drop zone that uploads straight away.
 */
export default function Attachments({
    attachments,
    uploadUrl,
    reload,
    can,
}: {
    attachments: AttachmentItem[];
    uploadUrl: string;
    reload: string[];
    /** Whether files may be added and deleted, under the team's access rules. */
    can: Abilities;
}) {
    const input = useRef<HTMLInputElement>(null);
    const [isOver, setIsOver] = useState(false);
    const [uploading, setUploading] = useState(false);

    const upload = (files: FileList | null) => {
        if (!files || files.length === 0) {
            return;
        }

        router.post(
            uploadUrl,
            { files: Array.from(files) },
            {
                forceFormData: true,
                only: reload,
                preserveScroll: true,
                preserveState: true,
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

    const onDrop = (event: DragEvent) => {
        event.preventDefault();
        setIsOver(false);
        upload(event.dataTransfer.files);
    };

    return (
        <div className="space-y-2">
            {attachments.length > 0 && (
                <ul className="space-y-1">
                    {attachments.map((attachment) => {
                        const Icon = attachment.mime_type.startsWith('image/')
                            ? Image
                            : attachment.mime_type === 'application/pdf'
                              ? FileText
                              : Paperclip;

                        return (
                            <li
                                key={attachment.id}
                                className="group flex h-10 items-center gap-2.5 rounded-md border bg-card px-2.5 text-sm"
                            >
                                <Icon className="size-4 shrink-0 text-subtle-foreground" />
                                <a
                                    href={attachment.url}
                                    className="min-w-0 flex-1 truncate hover:underline"
                                >
                                    {attachment.name}
                                </a>
                                <span className="shrink-0 text-xs text-subtle-foreground">
                                    {formatBytes(attachment.size)}
                                    {attachment.uploaded_by &&
                                        ` · ${attachment.uploaded_by}`}
                                    {` · ${timeAgo(attachment.created_at)}`}
                                </span>
                                {can.delete && (
                                    <button
                                        type="button"
                                        aria-label={`Delete ${attachment.name}`}
                                        onClick={() =>
                                            router.delete(
                                                destroy.url(attachment.id),
                                                {
                                                    only: reload,
                                                    preserveScroll: true,
                                                    preserveState: true,
                                                },
                                            )
                                        }
                                        className="text-subtle-foreground opacity-0 group-hover:opacity-100 hover:text-destructive focus-visible:opacity-100"
                                    >
                                        <Trash2 className="size-3.5" />
                                    </button>
                                )}
                            </li>
                        );
                    })}
                </ul>
            )}
            {can.create && (
                <>
                    <button
                        type="button"
                        onClick={() => input.current?.click()}
                        onDragOver={(event) => {
                            event.preventDefault();
                            setIsOver(true);
                        }}
                        onDragLeave={() => setIsOver(false)}
                        onDrop={onDrop}
                        className={cn(
                            'flex w-full items-center justify-center gap-2 rounded-md border border-dashed px-3 py-3 text-sm text-muted-foreground transition-colors hover:border-border-strong hover:text-foreground',
                            isOver &&
                                'border-primary bg-primary/5 text-foreground',
                        )}
                    >
                        <Upload className="size-4" />
                        {uploading
                            ? 'Uploading…'
                            : 'Drop files here or click to upload'}
                    </button>
                    <input
                        ref={input}
                        type="file"
                        multiple
                        hidden
                        onChange={(event) => {
                            upload(event.target.files);
                            event.target.value = '';
                        }}
                    />
                </>
            )}
        </div>
    );
}
