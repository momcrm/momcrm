import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import {
    Archive,
    ArchiveRestore,
    Building2,
    CalendarClock,
    FileText,
    Folder,
    History,
    Layers,
    Link2,
    MonitorSmartphone,
    MoreHorizontal,
    Pencil,
    Ticket,
    Trash2,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import {
    ArticleStatusPill,
    ClassificationBadge,
} from '@/components/articles/article-badges';
import ArticleBody from '@/components/articles/article-body';
import MarkReviewedDialog from '@/components/articles/mark-reviewed-dialog';
import ConfirmDelete from '@/components/confirm-delete';
import EmptyState from '@/components/empty-state';
import { LabelChips } from '@/components/labels';
import SoftBadge from '@/components/soft-badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useInitials } from '@/hooks/use-initials';
import { timeAgo } from '@/lib/format';
import {
    archive,
    destroy,
    edit,
    history,
    index,
    restore,
    show,
} from '@/routes/articles';
import { articles as customerArticles } from '@/routes/customers';
import type { Abilities, Article } from '@/types';

type LinkedGroup = {
    kind: string;
    records: { link_id: string; id: string; title: string; url: string }[];
};

/** The icon beside each kind of record an article can be attached to. */
const kindIcons: Record<string, LucideIcon> = {
    Tickets: Ticket,
    Assets: MonitorSmartphone,
    Services: Layers,
};

/** A timestamp or a YYYY-MM-DD day, such as "20 Nov 2026". */
function formatDate(iso: string | null): string {
    return iso
        ? new Date(
              iso.length === 10 ? `${iso}T00:00:00` : iso,
          ).toLocaleDateString(undefined, {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          })
        : '';
}

/** Minutes to read the body at about 220 words a minute, at least one. */
function readingMinutes(html: string): number {
    const words = html
        .replace(/<[^>]+>/g, ' ')
        .split(/\s+/u)
        .filter(Boolean).length;

    return Math.max(1, Math.round(words / 220));
}

export default function ArticleShow({
    article,
    categoryPath,
    linkedTo,
    can,
}: {
    article: Article;
    categoryPath: string | null;
    /** The tickets, assets and services it is attached to, by kind. */
    linkedTo: LinkedGroup[];
    can: Abilities;
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Knowledge base', href: index() },
            { title: article.title, href: show(article.id) },
        ],
    });

    const getInitials = useInitials();
    const [isDeleting, setIsDeleting] = useState(false);
    const minutes = useMemo(
        () => (article.body ? readingMinutes(article.body) : 0),
        [article.body],
    );

    const canEdit = can.update && !article.is_archived;
    const canArchive = can.archive && !article.is_archived;
    const canRestore = can.restore && article.is_archived;
    const hasMenu = canArchive || canRestore || can.delete;

    return (
        <>
            <Head title={article.title} />

            <div className="mx-auto w-full max-w-6xl px-4 py-6 md:px-8 md:py-10">
                <div className="grid gap-10 lg:grid-cols-[minmax(0,1fr)_17.5rem] xl:gap-14">
                    <article className="min-w-0">
                        <header className="border-b pb-7">
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <div className="flex min-w-0 flex-wrap items-center gap-1.5 text-[13px] text-muted-foreground">
                                    <FileText className="size-3.5 shrink-0" />
                                    {categoryPath ? (
                                        <span className="truncate">
                                            {categoryPath}
                                        </span>
                                    ) : (
                                        <Link
                                            href={index()}
                                            className="hover:text-foreground"
                                        >
                                            Knowledge base
                                        </Link>
                                    )}
                                </div>

                                <div className="ml-auto flex flex-wrap items-center gap-1.5">
                                    {article.version && (
                                        <Button variant="ghost" asChild>
                                            <Link href={history(article.id)}>
                                                <History />
                                                History
                                            </Link>
                                        </Button>
                                    )}
                                    {canEdit && (
                                        <MarkReviewedDialog
                                            articleId={article.id}
                                        />
                                    )}
                                    {canEdit && (
                                        <Button asChild>
                                            <Link href={edit(article.id)}>
                                                <Pencil />
                                                Edit
                                            </Link>
                                        </Button>
                                    )}
                                    {hasMenu && (
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label="More actions"
                                                >
                                                    <MoreHorizontal />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent
                                                align="end"
                                                className="w-44"
                                            >
                                                {canArchive && (
                                                    <DropdownMenuItem
                                                        onSelect={() =>
                                                            router.post(
                                                                archive.url(
                                                                    article.id,
                                                                ),
                                                            )
                                                        }
                                                    >
                                                        <Archive />
                                                        Archive
                                                    </DropdownMenuItem>
                                                )}
                                                {canRestore && (
                                                    <DropdownMenuItem
                                                        onSelect={() =>
                                                            router.post(
                                                                restore.url(
                                                                    article.id,
                                                                ),
                                                            )
                                                        }
                                                    >
                                                        <ArchiveRestore />
                                                        Restore
                                                    </DropdownMenuItem>
                                                )}
                                                {can.delete &&
                                                    (canArchive ||
                                                        canRestore) && (
                                                        <DropdownMenuSeparator />
                                                    )}
                                                {can.delete && (
                                                    <DropdownMenuItem
                                                        variant="destructive"
                                                        onSelect={() =>
                                                            setIsDeleting(true)
                                                        }
                                                    >
                                                        <Trash2 />
                                                        Delete
                                                    </DropdownMenuItem>
                                                )}
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    )}
                                </div>
                            </div>

                            <h1 className="mt-6 text-[28px] leading-[1.15] font-semibold tracking-[-0.025em] text-balance md:text-[34px]">
                                {article.title}
                            </h1>

                            {article.summary && (
                                <p className="mt-3 max-w-[38rem] text-[16.5px] leading-relaxed text-pretty text-soft-foreground">
                                    {article.summary}
                                </p>
                            )}

                            <div className="mt-6 flex flex-wrap items-center gap-x-3 gap-y-2 text-[13px] text-muted-foreground">
                                {article.author_name && (
                                    <span className="flex items-center gap-2 text-foreground">
                                        <span
                                            aria-hidden
                                            className="flex size-6 items-center justify-center rounded-full bg-primary/10 text-[10.5px] font-semibold text-primary"
                                        >
                                            {getInitials(article.author_name)}
                                        </span>
                                        {article.author_name}
                                    </span>
                                )}
                                {article.updated_at && (
                                    <Dotted>
                                        Updated {timeAgo(article.updated_at)}
                                    </Dotted>
                                )}
                                {minutes > 0 && (
                                    <Dotted>{minutes} min read</Dotted>
                                )}
                            </div>
                        </header>

                        {article.is_review_due && (
                            <div className="mt-6 flex items-start gap-3 rounded-lg border border-warning/25 bg-warning/8 px-4 py-3 text-[13.5px]">
                                <CalendarClock className="mt-0.5 size-4 shrink-0 text-warning" />
                                <div className="min-w-0 flex-1">
                                    <span className="font-medium">
                                        Due for review
                                    </span>{' '}
                                    <span className="text-soft-foreground">
                                        since{' '}
                                        {formatDate(article.next_review_on)}.
                                        Check it is still right, then mark it
                                        reviewed.
                                    </span>
                                </div>
                            </div>
                        )}

                        {article.is_archived && (
                            <div className="mt-6 flex items-start gap-3 rounded-lg border bg-muted/60 px-4 py-3 text-[13.5px] text-soft-foreground">
                                <Archive className="mt-0.5 size-4 shrink-0" />
                                <span>
                                    This article is archived. It is kept for
                                    reference and can't be edited
                                    {can.restore ? ' until it is restored' : ''}
                                    .
                                </span>
                            </div>
                        )}

                        <div className="mt-8">
                            {article.body ? (
                                <ArticleBody
                                    html={article.body}
                                    className="article-body-reading"
                                />
                            ) : (
                                <EmptyState
                                    icon={Pencil}
                                    title="Nothing written yet"
                                    description="This article has a title but no body. Open the editor to write it."
                                    action={
                                        canEdit && (
                                            <Button
                                                variant="outline"
                                                asChild
                                                className="mt-1"
                                            >
                                                <Link href={edit(article.id)}>
                                                    <Pencil />
                                                    Start writing
                                                </Link>
                                            </Button>
                                        )
                                    }
                                />
                            )}
                        </div>
                    </article>

                    <aside className="space-y-4 lg:sticky lg:top-[calc(46px+1.5rem)] lg:-m-1 lg:max-h-[calc(100svh-46px-3rem)] lg:[scrollbar-width:thin] lg:self-start lg:overflow-y-auto lg:p-1">
                        <Panel title="Details">
                            <dl className="divide-y text-[13px]">
                                <Fact label="Status">
                                    <ArticleStatusPill
                                        status={article.status}
                                        isArchived={article.is_archived}
                                    />
                                </Fact>
                                <Fact label="Audience">
                                    <ClassificationBadge
                                        classification={article.classification}
                                        showInternal
                                    />
                                </Fact>
                                {article.version && (
                                    <Fact label="Version">
                                        <Link
                                            href={history(article.id)}
                                            className="tabular-nums hover:underline"
                                        >
                                            v{article.version}
                                        </Link>
                                    </Fact>
                                )}
                                {article.customer_id && (
                                    <Fact label="Customer">
                                        <Link
                                            href={customerArticles(
                                                article.customer_id,
                                            )}
                                            className="flex min-w-0 items-center gap-1.5 hover:underline"
                                        >
                                            <Building2 className="size-3.5 shrink-0 text-muted-foreground" />
                                            <span className="truncate">
                                                {article.customer_name ??
                                                    'Customer'}
                                            </span>
                                        </Link>
                                    </Fact>
                                )}
                                {categoryPath && (
                                    <Fact label="Category">
                                        <span className="flex min-w-0 items-center gap-1.5">
                                            <Folder className="size-3.5 shrink-0 text-muted-foreground" />
                                            <span className="truncate">
                                                {categoryPath}
                                            </span>
                                        </span>
                                    </Fact>
                                )}
                                {article.published_at && (
                                    <Fact label="Published">
                                        {formatDate(article.published_at)}
                                    </Fact>
                                )}
                                {article.last_reviewed_on && (
                                    <Fact label="Last reviewed">
                                        {formatDate(article.last_reviewed_on)}
                                    </Fact>
                                )}
                                {article.next_review_on && (
                                    <Fact label="Next review">
                                        {article.is_review_due ? (
                                            <SoftBadge tone="warning">
                                                {formatDate(
                                                    article.next_review_on,
                                                )}
                                            </SoftBadge>
                                        ) : (
                                            formatDate(article.next_review_on)
                                        )}
                                    </Fact>
                                )}
                            </dl>
                            {article.labels.length > 0 && (
                                <div className="border-t px-4 py-3">
                                    <LabelChips labels={article.labels} />
                                </div>
                            )}
                        </Panel>

                        {linkedTo.length > 0 && (
                            <Panel title="Attached to">
                                <div className="space-y-3 px-4 pb-4">
                                    {linkedTo.map((group) => {
                                        const Icon =
                                            kindIcons[group.kind] ?? Link2;

                                        return (
                                            <div key={group.kind}>
                                                <div className="mb-1 text-[11.5px] font-medium text-subtle-foreground">
                                                    {group.kind}
                                                </div>
                                                <ul className="-mx-2">
                                                    {group.records.map(
                                                        (record) => (
                                                            <li
                                                                key={
                                                                    record.link_id
                                                                }
                                                            >
                                                                <Link
                                                                    href={
                                                                        record.url
                                                                    }
                                                                    className="flex items-center gap-2 rounded-md px-2 py-1.5 text-[13px] hover:bg-accent"
                                                                >
                                                                    <Icon className="size-3.5 shrink-0 text-muted-foreground" />
                                                                    <span className="truncate">
                                                                        {
                                                                            record.title
                                                                        }
                                                                    </span>
                                                                </Link>
                                                            </li>
                                                        ),
                                                    )}
                                                </ul>
                                            </div>
                                        );
                                    })}
                                </div>
                            </Panel>
                        )}
                    </aside>
                </div>
            </div>

            {can.delete && (
                <ConfirmDelete
                    trigger={null}
                    open={isDeleting}
                    onOpenChange={setIsDeleting}
                    action={destroy.form(article.id)}
                    title={`Delete ${article.title}?`}
                    description="The article is gone for good. Archive it instead to keep it out of the way."
                />
            )}
        </>
    );
}

/** A byline item with a dot before it. */
function Dotted({ children }: { children: ReactNode }) {
    return (
        <span className="flex items-center gap-3 before:size-[3px] before:rounded-full before:bg-border-strong">
            {children}
        </span>
    );
}

function Panel({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="rounded-xl border bg-card shadow-xs">
            <h2 className="flex h-10 items-center px-4 text-[13px] font-medium">
                {title}
            </h2>
            {children}
        </section>
    );
}

/** One row of the details panel: a label on the left, its value on the right. */
function Fact({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="flex min-h-10 items-center justify-between gap-3 px-4 py-2 first:border-t">
            <dt className="shrink-0 text-muted-foreground">{label}</dt>
            <dd className="flex min-w-0 justify-end text-right">{children}</dd>
        </div>
    );
}
