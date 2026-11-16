import { Head, Link, router } from '@inertiajs/react';
import {
    ChevronRight,
    Download,
    FileText,
    Folder,
    FolderInput,
    FolderPlus,
    Image,
    MoreHorizontal,
    Paperclip,
    Pencil,
    Trash2,
    Upload,
} from 'lucide-react';
import { useRef, useState } from 'react';
import type { DragEvent, FormEvent, ReactNode } from 'react';
import { toast } from 'sonner';
import { TabHeader } from '@/components/customers/workspace-tab';
import EmptyState from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useWorkspaceBreadcrumbs } from '@/layouts/customer/layout';
import { formatBytes, timeAgo } from '@/lib/format';
import { cn } from '@/lib/utils';
import {
    destroy as destroyFile,
    update as updateFile,
} from '@/routes/attachments';
import { files as filesTab } from '@/routes/customers';
import { store as storeFiles } from '@/routes/customers/files';
import {
    destroy as destroyFolder,
    store as storeFolder,
    update as updateFolder,
} from '@/routes/folders';
import type {
    Abilities,
    AttachmentItem,
    CustomerWorkspace,
    FolderItem,
} from '@/types';

const RELOAD = ['files', 'folders', 'folderId', 'workspace'];

const DRAG_TYPE = 'application/x-momcrm-file';

const visit = {
    only: RELOAD,
    preserveScroll: true,
    preserveState: true,
    onError: (errors: Record<string, string>) => {
        const message = Object.values(errors)[0];

        if (message) {
            toast.error(message);
        }
    },
};

/**
 * The folders from the top level down to the open one.
 */
function pathTo(folders: FolderItem[], folderId: string | null): FolderItem[] {
    const path: FolderItem[] = [];
    let current = folders.find((folder) => folder.id === folderId);

    while (current) {
        path.unshift(current);
        current = folders.find((folder) => folder.id === current?.parent_id);
    }

    return path;
}

/**
 * Every folder as an indented list, parents before their children, for
 * "Move to" menus.
 */
function flatTree(
    folders: FolderItem[],
    parentId: string | null = null,
    depth = 0,
): { folder: FolderItem; depth: number }[] {
    return folders
        .filter((folder) => folder.parent_id === parentId)
        .flatMap((folder) => [
            { folder, depth },
            ...flatTree(folders, folder.id, depth + 1),
        ]);
}

function FileIcon({ mimeType }: { mimeType: string }) {
    const Icon = mimeType.startsWith('image/')
        ? Image
        : mimeType === 'application/pdf'
          ? FileText
          : Paperclip;

    return <Icon className="size-4 shrink-0 text-subtle-foreground" />;
}

/**
 * The folder tree beside the file list. Files dragged onto a folder move
 * into it.
 */
function FolderTree({
    folders,
    parentId,
    openId,
    customerId,
    onDropFile,
    depth = 0,
}: {
    folders: FolderItem[];
    parentId: string | null;
    openId: string | null;
    customerId: string;
    onDropFile: (fileId: string, folderId: string | null) => void;
    depth?: number;
}) {
    const children = folders.filter((folder) => folder.parent_id === parentId);
    const openPath = pathTo(folders, openId).map((folder) => folder.id);

    return (
        <ul>
            {children.map((folder) => (
                <li key={folder.id}>
                    <DropTarget onDropFile={(id) => onDropFile(id, folder.id)}>
                        <Link
                            href={filesTab(customerId, {
                                query: { folder: folder.id },
                            })}
                            only={RELOAD}
                            preserveState
                            preserveScroll
                            style={{ paddingLeft: `${8 + depth * 14}px` }}
                            className={cn(
                                'flex h-7 items-center gap-1.5 rounded-md pr-2 text-[13px] text-muted-foreground hover:bg-accent hover:text-foreground',
                                folder.id === openId &&
                                    'bg-accent font-medium text-foreground',
                            )}
                        >
                            <Folder className="size-3.5 shrink-0" />
                            <span className="truncate">{folder.name}</span>
                        </Link>
                    </DropTarget>
                    {openPath.includes(folder.id) && (
                        <FolderTree
                            folders={folders}
                            parentId={folder.id}
                            openId={openId}
                            customerId={customerId}
                            onDropFile={onDropFile}
                            depth={depth + 1}
                        />
                    )}
                </li>
            ))}
        </ul>
    );
}

/**
 * Highlights while a file is dragged over it, and reports the file dropped.
 */
function DropTarget({
    onDropFile,
    children,
    className,
}: {
    onDropFile: (fileId: string) => void;
    children: ReactNode;
    className?: string;
}) {
    const [isOver, setIsOver] = useState(false);

    return (
        <div
            className={cn(
                'rounded-md',
                isOver && 'ring-2 ring-primary/60',
                className,
            )}
            onDragOver={(event) => {
                if (event.dataTransfer.types.includes(DRAG_TYPE)) {
                    event.preventDefault();
                    setIsOver(true);
                }
            }}
            onDragLeave={() => setIsOver(false)}
            onDrop={(event) => {
                const fileId = event.dataTransfer.getData(DRAG_TYPE);
                setIsOver(false);

                if (fileId) {
                    event.preventDefault();
                    event.stopPropagation();
                    onDropFile(fileId);
                }
            }}
        >
            {children}
        </div>
    );
}

/**
 * Asks for a name, to make or rename a folder.
 */
function NameDialog({
    title,
    initial,
    submitLabel,
    onSubmit,
    onClose,
}: {
    title: string;
    initial: string;
    submitLabel: string;
    onSubmit: (name: string) => void;
    onClose: () => void;
}) {
    const [name, setName] = useState(initial);

    return (
        <Dialog open onOpenChange={(isOpen) => !isOpen && onClose()}>
            <DialogContent className="sm:max-w-sm">
                <form
                    onSubmit={(event: FormEvent) => {
                        event.preventDefault();

                        if (name.trim()) {
                            onSubmit(name.trim());
                        }
                    }}
                    className="space-y-4"
                >
                    <DialogHeader>
                        <DialogTitle>{title}</DialogTitle>
                    </DialogHeader>
                    <Input
                        aria-label="Folder name"
                        value={name}
                        onChange={(event) => setName(event.target.value)}
                        maxLength={255}
                        autoFocus
                    />
                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={onClose}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={!name.trim()}>
                            {submitLabel}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DescriptionDialog({
    file,
    onClose,
}: {
    file: AttachmentItem;
    onClose: () => void;
}) {
    const [description, setDescription] = useState(file.description ?? '');

    return (
        <Dialog open onOpenChange={(isOpen) => !isOpen && onClose()}>
            <DialogContent className="sm:max-w-md">
                <form
                    onSubmit={(event: FormEvent) => {
                        event.preventDefault();
                        router.patch(
                            updateFile.url(file.id),
                            { description },
                            { ...visit, onSuccess: onClose },
                        );
                    }}
                    className="space-y-4"
                >
                    <DialogHeader>
                        <DialogTitle className="truncate">
                            {file.name}
                        </DialogTitle>
                    </DialogHeader>
                    <Textarea
                        aria-label="Description"
                        placeholder="What this file is, such as “Signed copy, March”."
                        rows={4}
                        maxLength={5000}
                        value={description}
                        onChange={(event) => setDescription(event.target.value)}
                        autoFocus
                    />
                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={onClose}>
                            Cancel
                        </Button>
                        <Button type="submit">Save</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function MoveMenu({
    folders,
    currentId,
    onMove,
}: {
    folders: FolderItem[];
    currentId: string | null;
    onMove: (folderId: string | null) => void;
}) {
    return (
        <DropdownMenuSub>
            <DropdownMenuSubTrigger>
                <FolderInput className="size-4" />
                Move to
            </DropdownMenuSubTrigger>
            <DropdownMenuSubContent className="max-h-72 w-56 overflow-y-auto">
                <DropdownMenuItem
                    disabled={currentId === null}
                    onSelect={() => onMove(null)}
                >
                    Top level
                </DropdownMenuItem>
                {flatTree(folders).map(({ folder, depth }) => (
                    <DropdownMenuItem
                        key={folder.id}
                        disabled={folder.id === currentId}
                        onSelect={() => onMove(folder.id)}
                        style={{ paddingLeft: `${8 + depth * 14}px` }}
                    >
                        <Folder className="size-3.5" />
                        <span className="truncate">{folder.name}</span>
                    </DropdownMenuItem>
                ))}
            </DropdownMenuSubContent>
        </DropdownMenuSub>
    );
}

export default function CustomerFiles({
    workspace,
    folders,
    folderId,
    files,
    accept,
    fileCan,
}: {
    workspace: CustomerWorkspace;
    folders: FolderItem[];
    folderId: string | null;
    files: AttachmentItem[];
    accept: string;
    fileCan: Abilities;
}) {
    const { customer } = workspace;
    useWorkspaceBreadcrumbs(customer, {
        title: 'Files',
        href: filesTab(customer.id),
    });

    const input = useRef<HTMLInputElement>(null);
    const [isOver, setIsOver] = useState(false);
    const [isUploading, setIsUploading] = useState(false);
    const [dialog, setDialog] = useState<
        | { kind: 'new-folder' }
        | { kind: 'rename'; folder: FolderItem }
        | { kind: 'describe'; file: AttachmentItem }
        | null
    >(null);

    const path = pathTo(folders, folderId);
    const subfolders = folders.filter(
        (folder) => folder.parent_id === folderId,
    );
    const canWrite = Boolean(fileCan.create);

    const upload = (list: FileList | null) => {
        if (!list || list.length === 0) {
            return;
        }

        router.post(
            storeFiles.url(customer.id),
            { files: Array.from(list), folder_id: folderId ?? '' },
            {
                ...visit,
                forceFormData: true,
                onStart: () => setIsUploading(true),
                onFinish: () => setIsUploading(false),
            },
        );
    };

    const moveFile = (fileId: string, targetId: string | null) =>
        router.patch(updateFile.url(fileId), { folder_id: targetId }, visit);

    const moveFolder = (folder: FolderItem, targetId: string | null) =>
        router.patch(
            updateFolder.url(folder.id),
            { parent_id: targetId },
            visit,
        );

    const onDropUpload = (event: DragEvent) => {
        if (event.dataTransfer.types.includes('Files')) {
            event.preventDefault();
            setIsOver(false);
            upload(event.dataTransfer.files);
        }
    };

    const folderHref = (id: string | null) =>
        filesTab(customer.id, { query: { folder: id ?? undefined } });

    return (
        <>
            <Head title={`${customer.name} · Files`} />
            <TabHeader
                title="Files"
                description="Documents, photos and diagrams for this customer, sorted into folders."
                action={
                    canWrite && (
                        <div className="flex gap-1.5">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    setDialog({ kind: 'new-folder' })
                                }
                            >
                                <FolderPlus className="size-4" />
                                New folder
                            </Button>
                            <Button
                                size="sm"
                                disabled={isUploading}
                                onClick={() => input.current?.click()}
                            >
                                <Upload className="size-4" />
                                {isUploading ? 'Uploading…' : 'Upload'}
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
                        </div>
                    )
                }
            />

            <div className="flex flex-col gap-4 md:flex-row">
                {folders.length > 0 && (
                    <aside
                        aria-label="Folders"
                        className="w-full shrink-0 rounded-lg border bg-card p-1.5 md:w-52"
                    >
                        <DropTarget onDropFile={(id) => moveFile(id, null)}>
                            <Link
                                href={folderHref(null)}
                                only={RELOAD}
                                preserveState
                                preserveScroll
                                className={cn(
                                    'flex h-7 items-center gap-1.5 rounded-md px-2 text-[13px] text-muted-foreground hover:bg-accent hover:text-foreground',
                                    folderId === null &&
                                        'bg-accent font-medium text-foreground',
                                )}
                            >
                                <Folder className="size-3.5" />
                                All files
                            </Link>
                        </DropTarget>
                        <FolderTree
                            folders={folders}
                            parentId={null}
                            openId={folderId}
                            customerId={customer.id}
                            onDropFile={moveFile}
                        />
                    </aside>
                )}

                <div
                    className={cn(
                        'min-w-0 flex-1 rounded-lg',
                        isOver && 'ring-2 ring-primary/60',
                    )}
                    onDragOver={(event) => {
                        if (
                            canWrite &&
                            event.dataTransfer.types.includes('Files')
                        ) {
                            event.preventDefault();
                            setIsOver(true);
                        }
                    }}
                    onDragLeave={() => setIsOver(false)}
                    onDrop={onDropUpload}
                >
                    <nav
                        aria-label="Folder path"
                        className="mb-2 flex flex-wrap items-center gap-1 text-[13px]"
                    >
                        <Link
                            href={folderHref(null)}
                            only={RELOAD}
                            preserveState
                            className="text-muted-foreground hover:text-foreground"
                        >
                            All files
                        </Link>
                        {path.map((folder) => (
                            <span
                                key={folder.id}
                                className="flex items-center gap-1"
                            >
                                <ChevronRight className="size-3.5 text-subtle-foreground" />
                                <Link
                                    href={folderHref(folder.id)}
                                    only={RELOAD}
                                    preserveState
                                    className={cn(
                                        'hover:text-foreground',
                                        folder.id === folderId
                                            ? 'font-medium'
                                            : 'text-muted-foreground',
                                    )}
                                >
                                    {folder.name}
                                </Link>
                            </span>
                        ))}
                    </nav>

                    {subfolders.length === 0 && files.length === 0 ? (
                        <EmptyState
                            title={
                                folderId
                                    ? 'This folder is empty'
                                    : 'No files yet'
                            }
                            description={
                                canWrite
                                    ? 'Drop files here, or use Upload. Each file can be up to 20 MB.'
                                    : 'Files added for this customer will show up here.'
                            }
                        />
                    ) : (
                        <ul className="divide-y overflow-hidden rounded-lg border bg-card">
                            {subfolders.map((folder) => (
                                <li key={folder.id}>
                                    <DropTarget
                                        onDropFile={(id) =>
                                            moveFile(id, folder.id)
                                        }
                                        className="group flex h-11 items-center gap-2.5 rounded-none px-3 text-sm hover:bg-accent/50"
                                    >
                                        <Folder className="size-4 shrink-0 text-subtle-foreground" />
                                        <Link
                                            href={folderHref(folder.id)}
                                            only={RELOAD}
                                            preserveState
                                            className="min-w-0 flex-1 truncate font-medium hover:underline"
                                        >
                                            {folder.name}
                                        </Link>
                                        {canWrite && (
                                            <RowMenu label={folder.name}>
                                                <DropdownMenuItem
                                                    onSelect={() =>
                                                        setDialog({
                                                            kind: 'rename',
                                                            folder,
                                                        })
                                                    }
                                                >
                                                    <Pencil className="size-4" />
                                                    Rename
                                                </DropdownMenuItem>
                                                <MoveMenu
                                                    folders={folders.filter(
                                                        (other) =>
                                                            !pathTo(
                                                                folders,
                                                                other.id,
                                                            ).some(
                                                                (step) =>
                                                                    step.id ===
                                                                    folder.id,
                                                            ),
                                                    )}
                                                    currentId={folder.parent_id}
                                                    onMove={(target) =>
                                                        moveFolder(
                                                            folder,
                                                            target,
                                                        )
                                                    }
                                                />
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem
                                                    variant="destructive"
                                                    onSelect={() =>
                                                        router.delete(
                                                            destroyFolder.url(
                                                                folder.id,
                                                            ),
                                                            visit,
                                                        )
                                                    }
                                                >
                                                    <Trash2 className="size-4" />
                                                    Delete folder
                                                </DropdownMenuItem>
                                            </RowMenu>
                                        )}
                                    </DropTarget>
                                </li>
                            ))}
                            {files.map((file) => (
                                <li
                                    key={file.id}
                                    draggable={canWrite}
                                    onDragStart={(event) => {
                                        event.dataTransfer.setData(
                                            DRAG_TYPE,
                                            file.id,
                                        );
                                        event.dataTransfer.effectAllowed =
                                            'move';
                                    }}
                                    className="group flex min-h-11 items-center gap-2.5 px-3 py-1.5 text-sm"
                                >
                                    <FileIcon mimeType={file.mime_type} />
                                    <div className="min-w-0 flex-1">
                                        <a
                                            href={file.url}
                                            className="block truncate hover:underline"
                                        >
                                            {file.name}
                                        </a>
                                        {file.description && (
                                            <p className="truncate text-xs text-muted-foreground">
                                                {file.description}
                                            </p>
                                        )}
                                    </div>
                                    <span className="hidden shrink-0 text-xs text-subtle-foreground sm:block">
                                        {formatBytes(file.size)}
                                        {file.uploaded_by &&
                                            ` · ${file.uploaded_by}`}
                                        {` · ${timeAgo(file.created_at)}`}
                                    </span>
                                    <RowMenu label={file.name}>
                                        <DropdownMenuItem asChild>
                                            <a href={file.url}>
                                                <Download className="size-4" />
                                                Download
                                            </a>
                                        </DropdownMenuItem>
                                        {fileCan.update && (
                                            <>
                                                <DropdownMenuItem
                                                    onSelect={() =>
                                                        setDialog({
                                                            kind: 'describe',
                                                            file,
                                                        })
                                                    }
                                                >
                                                    <Pencil className="size-4" />
                                                    Edit description
                                                </DropdownMenuItem>
                                                <MoveMenu
                                                    folders={folders}
                                                    currentId={folderId}
                                                    onMove={(target) =>
                                                        moveFile(
                                                            file.id,
                                                            target,
                                                        )
                                                    }
                                                />
                                            </>
                                        )}
                                        {fileCan.delete && (
                                            <>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem
                                                    variant="destructive"
                                                    onSelect={() =>
                                                        router.delete(
                                                            destroyFile.url(
                                                                file.id,
                                                            ),
                                                            visit,
                                                        )
                                                    }
                                                >
                                                    <Trash2 className="size-4" />
                                                    Delete
                                                </DropdownMenuItem>
                                            </>
                                        )}
                                    </RowMenu>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>

            {dialog?.kind === 'new-folder' && (
                <NameDialog
                    title="New folder"
                    initial=""
                    submitLabel="Create"
                    onClose={() => setDialog(null)}
                    onSubmit={(name) =>
                        router.post(
                            storeFolder.url(customer.id),
                            { name, parent_id: folderId },
                            { ...visit, onSuccess: () => setDialog(null) },
                        )
                    }
                />
            )}
            {dialog?.kind === 'rename' && (
                <NameDialog
                    title="Rename folder"
                    initial={dialog.folder.name}
                    submitLabel="Save"
                    onClose={() => setDialog(null)}
                    onSubmit={(name) =>
                        router.patch(
                            updateFolder.url(dialog.folder.id),
                            { name },
                            { ...visit, onSuccess: () => setDialog(null) },
                        )
                    }
                />
            )}
            {dialog?.kind === 'describe' && (
                <DescriptionDialog
                    file={dialog.file}
                    onClose={() => setDialog(null)}
                />
            )}
        </>
    );
}

function RowMenu({ label, children }: { label: string; children: ReactNode }) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <button
                    type="button"
                    aria-label={`Actions for ${label}`}
                    className="flex size-7 shrink-0 items-center justify-center rounded-md text-subtle-foreground opacity-60 group-hover:opacity-100 hover:bg-accent hover:text-foreground focus-visible:opacity-100 data-[state=open]:opacity-100"
                >
                    <MoreHorizontal className="size-4" />
                </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-48">
                {children}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
