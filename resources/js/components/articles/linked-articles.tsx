import { Link, router, useHttp } from '@inertiajs/react';
import { BookOpen, Plus, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import {
    ArticleStatusPill,
    ClassificationBadge,
} from '@/components/articles/article-badges';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { destroy, options, store } from '@/routes/article-links';
import { show } from '@/routes/articles';
import type {
    ArticleLinkTarget,
    ArticleStatus,
    AttachedArticle,
} from '@/types';

type ArticleOption = {
    id: string;
    title: string;
    summary: string | null;
    status: ArticleStatus;
};

/**
 * Searches the articles the person may read and attaches the one picked.
 */
function AttachArticleDialog({
    open,
    onOpenChange,
    attachedIds,
    onPick,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    attachedIds: string[];
    onPick: (articleId: string) => void;
}) {
    const [query, setQuery] = useState('');
    const [found, setFound] = useState<ArticleOption[] | null>(null);
    const http = useHttp<
        Record<string, never>,
        { articles: ArticleOption[] }
    >();

    useEffect(() => {
        if (!open) {
            return;
        }

        const timer = window.setTimeout(
            () => {
                http.cancel();
                void http
                    .get(options.url({ query: { q: query.trim() } }))
                    .then((response) => setFound(response.articles))
                    .catch(() => undefined);
            },
            query ? 200 : 0,
        );

        return () => window.clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, query]);

    const choices = (found ?? []).filter(
        (article) => !attachedIds.includes(article.id),
    );

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogTitle>Attach an article</DialogTitle>
                <DialogDescription>
                    It shows here and on the article.
                </DialogDescription>
                <Input
                    aria-label="Search articles"
                    value={query}
                    onChange={(event) => setQuery(event.target.value)}
                    placeholder="Search titles and text"
                    autoFocus
                />
                <ul className="-mx-2 max-h-72 overflow-y-auto">
                    {found !== null && choices.length === 0 && (
                        <li className="px-2 py-3 text-sm text-muted-foreground">
                            No articles to attach
                            {query ? ' match that.' : ' yet.'}
                        </li>
                    )}
                    {choices.map((article) => (
                        <li key={article.id}>
                            <button
                                type="button"
                                onClick={() => onPick(article.id)}
                                className="flex w-full items-start gap-2 rounded-md px-2 py-1.5 text-left hover:bg-muted"
                            >
                                <BookOpen className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                <span className="min-w-0 flex-1">
                                    <span className="block truncate text-sm">
                                        {article.title}
                                    </span>
                                    {article.summary && (
                                        <span className="block truncate text-xs text-muted-foreground">
                                            {article.summary}
                                        </span>
                                    )}
                                </span>
                                {article.status === 'draft' && (
                                    <ArticleStatusPill status="draft" />
                                )}
                            </button>
                        </li>
                    ))}
                </ul>
            </DialogContent>
        </Dialog>
    );
}

/**
 * The knowledge base articles attached to a ticket, asset or service, with
 * buttons to attach another or take one off for people who may change the
 * record.
 */
export default function LinkedArticles({
    target,
    recordId,
    articles,
    canEdit,
    reload,
}: {
    target: ArticleLinkTarget;
    recordId: string;
    articles: AttachedArticle[];
    canEdit: boolean;
    /** The props to reload after attaching or taking one off. */
    reload: string[];
}) {
    const [isPicking, setIsPicking] = useState(false);

    if (articles.length === 0 && !canEdit) {
        return null;
    }

    const attach = (articleId: string) => {
        setIsPicking(false);
        router.post(
            store.url([target, recordId]),
            { article_id: articleId },
            { only: reload, preserveScroll: true },
        );
    };

    return (
        <section className="space-y-2">
            <div className="flex items-center justify-between gap-2">
                <h3 className="text-xs font-medium tracking-wide text-subtle-foreground uppercase">
                    Articles
                </h3>
                {canEdit && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="h-7"
                        onClick={() => setIsPicking(true)}
                    >
                        <Plus />
                        Attach
                    </Button>
                )}
            </div>
            {articles.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    Attach the how-to that helps with this.
                </p>
            ) : (
                <ul className="divide-y rounded-lg border bg-card">
                    {articles.map((article) => (
                        <li
                            key={article.link_id}
                            className="flex items-center gap-2 px-3 py-2"
                        >
                            <BookOpen className="size-4 shrink-0 text-muted-foreground" />
                            <Link
                                href={show(article.id)}
                                className="min-w-0 flex-1 truncate text-sm hover:underline"
                            >
                                {article.title}
                            </Link>
                            <ClassificationBadge
                                classification={article.classification}
                            />
                            {(article.status === 'draft' ||
                                article.is_archived) && (
                                <ArticleStatusPill
                                    status={article.status}
                                    isArchived={article.is_archived}
                                />
                            )}
                            {canEdit && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    aria-label={`Take ${article.title} off`}
                                    onClick={() =>
                                        router.delete(
                                            destroy.url(article.link_id),
                                            {
                                                only: reload,
                                                preserveScroll: true,
                                            },
                                        )
                                    }
                                >
                                    <X />
                                </Button>
                            )}
                        </li>
                    ))}
                </ul>
            )}
            <AttachArticleDialog
                open={isPicking}
                onOpenChange={setIsPicking}
                attachedIds={articles.map((article) => article.id)}
                onPick={attach}
            />
        </section>
    );
}
