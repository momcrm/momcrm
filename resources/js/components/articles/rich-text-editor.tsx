import { TableKit } from '@tiptap/extension-table';
import { Placeholder } from '@tiptap/extensions';
import { EditorContent, useEditor, useEditorState } from '@tiptap/react';
import type { Editor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import {
    Bold,
    Code,
    Columns3,
    Grid2x2X,
    Heading2,
    Heading3,
    Heading4,
    Italic,
    Link2,
    List,
    ListOrdered,
    Minus,
    Quote,
    Redo2,
    Rows3,
    SquareCode,
    Strikethrough,
    Table,
    Trash2,
    Underline,
    Undo2,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';

const isMac =
    typeof navigator !== 'undefined' && /Mac/.test(navigator.platform);
const mod = isMac ? '⌘' : 'Ctrl+';

function ToolbarButton({
    label,
    icon: Icon,
    isActive = false,
    disabled = false,
    onClick,
}: {
    label: string;
    icon: LucideIcon;
    isActive?: boolean;
    disabled?: boolean;
    onClick: () => void;
}) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <button
                    type="button"
                    aria-label={label}
                    aria-pressed={isActive}
                    disabled={disabled}
                    onMouseDown={(event) => event.preventDefault()}
                    onClick={onClick}
                    className={cn(
                        'inline-flex size-7 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground disabled:pointer-events-none disabled:opacity-40',
                        isActive && 'bg-muted text-foreground',
                    )}
                >
                    <Icon className="size-4" />
                </button>
            </TooltipTrigger>
            <TooltipContent>{label}</TooltipContent>
        </Tooltip>
    );
}

function ToolbarGroup({ children }: { children: ReactNode }) {
    return (
        <div className="flex items-center gap-0.5 border-r pr-1 last:border-r-0">
            {children}
        </div>
    );
}

/**
 * Asks for a link's address. Saving an empty address removes the link.
 */
function LinkDialog({
    editor,
    open,
    onOpenChange,
}: {
    editor: Editor;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const [href, setHref] = useState('');

    const save = (event: FormEvent) => {
        event.preventDefault();
        const chain = editor.chain().focus().extendMarkRange('link');

        if (href.trim() === '') {
            chain.unsetLink().run();
        } else {
            chain.setLink({ href: href.trim() }).run();
        }

        onOpenChange(false);
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(isOpen) => {
                if (isOpen) {
                    setHref(editor.getAttributes('link').href ?? '');
                }

                onOpenChange(isOpen);
            }}
        >
            <DialogContent className="sm:max-w-md">
                <DialogTitle>Link</DialogTitle>
                <DialogDescription>
                    Leave it empty to remove the link.
                </DialogDescription>
                <form onSubmit={save} className="space-y-5">
                    <Input
                        aria-label="Address"
                        value={href}
                        onChange={(event) => setHref(event.target.value)}
                        placeholder="https://"
                        autoFocus
                    />
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => onOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button>Save link</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function Toolbar({ editor }: { editor: Editor }) {
    const [isLinkOpen, setIsLinkOpen] = useState(false);
    const state = useEditorState({
        editor,
        selector: ({ editor }) => ({
            bold: editor.isActive('bold'),
            italic: editor.isActive('italic'),
            underline: editor.isActive('underline'),
            strike: editor.isActive('strike'),
            code: editor.isActive('code'),
            h2: editor.isActive('heading', { level: 2 }),
            h3: editor.isActive('heading', { level: 3 }),
            h4: editor.isActive('heading', { level: 4 }),
            bulletList: editor.isActive('bulletList'),
            orderedList: editor.isActive('orderedList'),
            blockquote: editor.isActive('blockquote'),
            codeBlock: editor.isActive('codeBlock'),
            link: editor.isActive('link'),
            table: editor.isActive('table'),
            canUndo: editor.can().undo(),
            canRedo: editor.can().redo(),
        }),
    });
    const chain = () => editor.chain().focus();

    return (
        <div className="sticky top-0 z-10 flex flex-wrap items-center gap-1 rounded-t-lg border-b bg-card px-1.5 py-1">
            <ToolbarGroup>
                <ToolbarButton
                    label="Heading"
                    icon={Heading2}
                    isActive={state.h2}
                    onClick={() => chain().toggleHeading({ level: 2 }).run()}
                />
                <ToolbarButton
                    label="Subheading"
                    icon={Heading3}
                    isActive={state.h3}
                    onClick={() => chain().toggleHeading({ level: 3 }).run()}
                />
                <ToolbarButton
                    label="Small heading"
                    icon={Heading4}
                    isActive={state.h4}
                    onClick={() => chain().toggleHeading({ level: 4 }).run()}
                />
            </ToolbarGroup>
            <ToolbarGroup>
                <ToolbarButton
                    label={`Bold (${mod}B)`}
                    icon={Bold}
                    isActive={state.bold}
                    onClick={() => chain().toggleBold().run()}
                />
                <ToolbarButton
                    label={`Italic (${mod}I)`}
                    icon={Italic}
                    isActive={state.italic}
                    onClick={() => chain().toggleItalic().run()}
                />
                <ToolbarButton
                    label={`Underline (${mod}U)`}
                    icon={Underline}
                    isActive={state.underline}
                    onClick={() => chain().toggleUnderline().run()}
                />
                <ToolbarButton
                    label="Strikethrough"
                    icon={Strikethrough}
                    isActive={state.strike}
                    onClick={() => chain().toggleStrike().run()}
                />
                <ToolbarButton
                    label="Inline code"
                    icon={Code}
                    isActive={state.code}
                    onClick={() => chain().toggleCode().run()}
                />
                <ToolbarButton
                    label="Link"
                    icon={Link2}
                    isActive={state.link}
                    onClick={() => setIsLinkOpen(true)}
                />
            </ToolbarGroup>
            <ToolbarGroup>
                <ToolbarButton
                    label="Bulleted list"
                    icon={List}
                    isActive={state.bulletList}
                    onClick={() => chain().toggleBulletList().run()}
                />
                <ToolbarButton
                    label="Numbered list"
                    icon={ListOrdered}
                    isActive={state.orderedList}
                    onClick={() => chain().toggleOrderedList().run()}
                />
                <ToolbarButton
                    label="Quote"
                    icon={Quote}
                    isActive={state.blockquote}
                    onClick={() => chain().toggleBlockquote().run()}
                />
                <ToolbarButton
                    label="Code block"
                    icon={SquareCode}
                    isActive={state.codeBlock}
                    onClick={() => chain().toggleCodeBlock().run()}
                />
                <ToolbarButton
                    label="Divider"
                    icon={Minus}
                    onClick={() => chain().setHorizontalRule().run()}
                />
            </ToolbarGroup>
            <ToolbarGroup>
                <ToolbarButton
                    label="Table"
                    icon={Table}
                    isActive={state.table}
                    onClick={() =>
                        chain()
                            .insertTable({
                                rows: 3,
                                cols: 3,
                                withHeaderRow: true,
                            })
                            .run()
                    }
                />
                {state.table && (
                    <>
                        <ToolbarButton
                            label="Add a row"
                            icon={Rows3}
                            onClick={() => chain().addRowAfter().run()}
                        />
                        <ToolbarButton
                            label="Add a column"
                            icon={Columns3}
                            onClick={() => chain().addColumnAfter().run()}
                        />
                        <ToolbarButton
                            label="Delete the row"
                            icon={Trash2}
                            onClick={() => chain().deleteRow().run()}
                        />
                        <ToolbarButton
                            label="Delete the table"
                            icon={Grid2x2X}
                            onClick={() => chain().deleteTable().run()}
                        />
                    </>
                )}
            </ToolbarGroup>
            <ToolbarGroup>
                <ToolbarButton
                    label={`Undo (${mod}Z)`}
                    icon={Undo2}
                    disabled={!state.canUndo}
                    onClick={() => chain().undo().run()}
                />
                <ToolbarButton
                    label="Redo"
                    icon={Redo2}
                    disabled={!state.canRedo}
                    onClick={() => chain().redo().run()}
                />
            </ToolbarGroup>

            <LinkDialog
                editor={editor}
                open={isLinkOpen}
                onOpenChange={setIsLinkOpen}
            />
        </div>
    );
}

/**
 * A rich text editor for article bodies: headings, lists, quotes, code,
 * links and tables. It reports its HTML on every change; the server
 * sanitises it to the same set of elements before storing it.
 */
export default function RichTextEditor({
    value,
    onChange,
    placeholder = 'Write the article…',
    ariaLabel = 'Body',
}: {
    value: string;
    onChange: (html: string) => void;
    placeholder?: string;
    ariaLabel?: string;
}) {
    const editor = useEditor({
        immediatelyRender: false,
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3, 4] },
                link: {
                    openOnClick: false,
                    autolink: true,
                    defaultProtocol: 'https',
                    protocols: ['http', 'https', 'mailto'],
                },
            }),
            TableKit.configure({ table: { resizable: false } }),
            Placeholder.configure({ placeholder }),
        ],
        content: value,
        editorProps: {
            attributes: {
                'aria-label': ariaLabel,
                class: 'article-body min-h-80 px-4 py-3 outline-none',
            },
        },
        onUpdate: ({ editor }) =>
            onChange(editor.isEmpty ? '' : editor.getHTML()),
    });

    useEffect(() => {
        if (!editor) {
            return;
        }

        const current = editor.isEmpty ? '' : editor.getHTML();

        if (value !== current) {
            editor.commands.setContent(value, { emitUpdate: false });
        }
    }, [editor, value]);

    return (
        <div className="rounded-lg border border-border-strong bg-card focus-within:border-primary focus-within:ring-[3px] focus-within:ring-primary/10">
            {editor ? (
                <>
                    <Toolbar editor={editor} />
                    <EditorContent editor={editor} />
                </>
            ) : (
                <div className="min-h-80" />
            )}
        </div>
    );
}
