import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { Archive, ArchiveRestore, Pencil } from 'lucide-react';
import {
    ArticleStatusPill,
    ClassificationBadge,
} from '@/components/articles/article-badges';
import ArticleBody from '@/components/articles/article-body';
import ConfirmDelete from '@/components/confirm-delete';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { timeAgo } from '@/lib/format';
import {
    archive,
    destroy,
    edit,
    index,
    restore,
    show,
} from '@/routes/articles';
import type { Abilities, Article } from '@/types';

function formatDate(iso: string | null): string {
    return iso
        ? new Date(iso).toLocaleDateString(undefined, {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          })
        : '';
}

export default function ArticleShow({
    article,
    can,
}: {
    article: Article;
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
                            {can.update && !article.is_archived && (
                                <Button asChild>
                                    <Link href={edit(article.id)}>
                                        <Pencil />
                                        Edit
                                    </Link>
                                </Button>
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

                {article.body ? (
                    <ArticleBody html={article.body} />
                ) : (
                    <EmptyState
                        title="Nothing written yet"
                        description="Open the editor to write the article."
                    />
                )}
            </div>
        </>
    );
}
