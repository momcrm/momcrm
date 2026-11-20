import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { Archive, ArchiveRestore, History, Pencil } from 'lucide-react';
import {
    ArticleStatusPill,
    ClassificationBadge,
} from '@/components/articles/article-badges';
import ArticleBody from '@/components/articles/article-body';
import MarkReviewedDialog from '@/components/articles/mark-reviewed-dialog';
import ConfirmDelete from '@/components/confirm-delete';
import EmptyState from '@/components/empty-state';
import { LabelChips } from '@/components/labels';
import PageHeader from '@/components/page-header';
import SoftBadge from '@/components/soft-badge';
import { Button } from '@/components/ui/button';
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

export default function ArticleShow({
    article,
    categoryPath,
    linkedTo,
    can,
}: {
    article: Article;
    categoryPath: string | null;
    /** The tickets, assets and services it is attached to, by kind. */
    linkedTo: {
        kind: string;
        records: { link_id: string; id: string; title: string; url: string }[];
    }[];
    can: Abilities;
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Knowledge base', href: index() },
            { title: article.title, href: show(article.id) },
        ],
    });

    return (
        <>
            <Head title={article.title} />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={article.title}
                    description={article.summary ?? undefined}
                    actions={
                        <>
                            {article.version && (
                                <Button variant="ghost" asChild>
                                    <Link href={history(article.id)}>
                                        <History />
                                        History
                                    </Link>
                                </Button>
                            )}
                            {can.update && !article.is_archived && (
                                <Button asChild>
                                    <Link href={edit(article.id)}>
                                        <Pencil />
                                        Edit
                                    </Link>
                                </Button>
                            )}
                            {can.update && !article.is_archived && (
                                <MarkReviewedDialog articleId={article.id} />
                            )}
                            {can.archive && !article.is_archived && (
                                <Form {...archive.form(article.id)}>
                                    {({ processing }) => (
                                        <Button
                                            variant="outline"
                                            disabled={processing}
                                        >
                                            <Archive />
                                            Archive
                                        </Button>
                                    )}
                                </Form>
                            )}
                            {can.restore && article.is_archived && (
                                <Form {...restore.form(article.id)}>
                                    {({ processing }) => (
                                        <Button
                                            variant="outline"
                                            disabled={processing}
                                        >
                                            <ArchiveRestore />
                                            Restore
                                        </Button>
                                    )}
                                </Form>
                            )}
                            {can.delete && (
                                <ConfirmDelete
                                    action={destroy.form(article.id)}
                                    title={`Delete ${article.title}?`}
                                    description="The article is gone for good. Archive it instead to keep it out of the way."
                                />
                            )}
                        </>
                    }
                />

                <div className="flex flex-wrap items-center gap-2 text-[13px] text-muted-foreground">
                    <ArticleStatusPill
                        status={article.status}
                        isArchived={article.is_archived}
                    />
                    <ClassificationBadge
                        classification={article.classification}
                        showInternal
                    />
                    {article.version && (
                        <span className="tabular-nums">v{article.version}</span>
                    )}
                    {article.author_name && (
                        <span>By {article.author_name}</span>
                    )}
                    {article.published_at && (
                        <span>
                            · Published {formatDate(article.published_at)}
                        </span>
                    )}
                    <span>· Updated {timeAgo(article.updated_at)}</span>
                </div>

                {(article.customer_id ||
                    categoryPath ||
                    article.labels.length > 0 ||
                    article.next_review_on ||
                    article.last_reviewed_on) && (
                    <div className="-mt-3 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-[13px] text-muted-foreground">
                        {article.customer_id && (
                            <Link
                                href={customerArticles(article.customer_id)}
                                className="text-foreground hover:underline"
                            >
                                {article.customer_name ?? 'Customer'}
                            </Link>
                        )}
                        {categoryPath && <span>{categoryPath}</span>}
                        <LabelChips labels={article.labels} />
                        {article.is_review_due ? (
                            <SoftBadge tone="warning">
                                Review due {formatDate(article.next_review_on)}
                            </SoftBadge>
                        ) : (
                            article.next_review_on && (
                                <span>
                                    Next review{' '}
                                    {formatDate(article.next_review_on)}
                                </span>
                            )
                        )}
                        {article.last_reviewed_on && (
                            <span>
                                Last reviewed{' '}
                                {formatDate(article.last_reviewed_on)}
                            </span>
                        )}
                    </div>
                )}

                {article.body ? (
                    <ArticleBody html={article.body} />
                ) : (
                    <EmptyState
                        title="Nothing written yet"
                        description="Open the editor to write the article."
                    />
                )}

                {linkedTo.length > 0 && (
                    <section className="space-y-3 border-t pt-5">
                        <h2 className="text-sm font-medium">Attached to</h2>
                        <dl className="grid gap-3 sm:grid-cols-3">
                            {linkedTo.map((group) => (
                                <div key={group.kind} className="min-w-0">
                                    <dt className="text-xs text-muted-foreground">
                                        {group.kind}
                                    </dt>
                                    {group.records.map((record) => (
                                        <dd key={record.link_id}>
                                            <Link
                                                href={record.url}
                                                className="block truncate text-sm hover:underline"
                                            >
                                                {record.title}
                                            </Link>
                                        </dd>
                                    ))}
                                </div>
                            ))}
                        </dl>
                    </section>
                )}
            </div>
        </>
    );
}
