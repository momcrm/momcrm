import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { History, RotateCcw } from 'lucide-react';
import { useMemo, useState } from 'react';
import ArticleBody from '@/components/articles/article-body';
import { diffWords } from '@/components/articles/word-diff';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { timeAgo } from '@/lib/format';
import { cn } from '@/lib/utils';
import { history, index, show } from '@/routes/articles';
import { restore } from '@/routes/articles/versions';
import type { Abilities, ArticleListItem, ArticleVersionItem } from '@/types';

type SelectedVersion = ArticleVersionItem & {
    summary: string | null;
    body: string;
    body_text: string;
};

type PreviousVersion = { label: string; title: string; body_text: string };

function Changes({
    selected,
    previous,
}: {
    selected: SelectedVersion;
    previous: PreviousVersion | null;
}) {
    const parts = useMemo(
        () =>
            previous ? diffWords(previous.body_text, selected.body_text) : [],
        [previous, selected.body_text],
    );

    if (!previous) {
        return (
            <p className="text-sm text-muted-foreground">
                The first version: nothing to compare it with.
            </p>
        );
    }

    const hasChanges = parts.some((part) => part.kind !== 'same');

    return (
        <div className="space-y-3">
            {previous.title !== selected.title && (
                <p className="text-sm">
                    <span className="text-muted-foreground">Title: </span>
                    <del className="bg-destructive/10 text-destructive">
                        {previous.title}
                    </del>{' '}
                    <ins className="bg-success/10 text-success no-underline">
                        {selected.title}
                    </ins>
                </p>
            )}
            {hasChanges ? (
                <p className="text-[14px] leading-relaxed whitespace-pre-wrap">
                    {parts.map((part, index) =>
                        part.kind === 'same' ? (
                            <span key={index}>{part.text}</span>
                        ) : part.kind === 'added' ? (
                            <ins
                                key={index}
                                className="rounded-sm bg-success/12 text-success no-underline"
                            >
                                {part.text}
                            </ins>
                        ) : (
                            <del
                                key={index}
                                className="rounded-sm bg-destructive/10 text-destructive"
                            >
                                {part.text}
                            </del>
                        ),
                    )}
                </p>
            ) : (
                <p className="text-sm text-muted-foreground">
                    The text is the same as version {previous.label}; only its
                    title, summary, pictures, formatting or classification
                    changed.
                </p>
            )}
        </div>
    );
}

export default function ArticleHistory({
    article,
    versions,
    selected,
    previous,
    can,
}: {
    article: ArticleListItem;
    versions: ArticleVersionItem[];
    selected: SelectedVersion | null;
    previous: PreviousVersion | null;
    can: Abilities;
}) {
    const [view, setView] = useState<'changes' | 'article'>('changes');

    setLayoutProps({
        breadcrumbs: [
            { title: 'Knowledge base', href: index() },
            { title: article.title, href: show(article.id) },
            { title: 'History', href: history(article.id) },
        ],
    });

    return (
        <>
            <Head title={`${article.title} · History`} />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="History"
                    description={`Every published version of ${article.title}.`}
                />

                {!selected ? (
                    <EmptyState
                        icon={History}
                        title="No versions yet"
                        description="An article gets its first version when it is published. From then on, each change is kept."
                    />
                ) : (
                    <div className="grid gap-6 lg:grid-cols-[16rem_1fr]">
                        <nav aria-label="Versions" className="space-y-1">
                            {versions.map((version) => (
                                <Link
                                    key={version.id ?? 'current'}
                                    href={history(article.id, {
                                        query: version.id
                                            ? { version: version.id }
                                            : {},
                                    })}
                                    preserveScroll
                                    className={cn(
                                        'block rounded-md px-3 py-2 text-sm hover:bg-muted',
                                        version.id === selected.id &&
                                            'bg-muted',
                                    )}
                                >
                                    <span className="flex items-center justify-between gap-2">
                                        <span className="font-medium tabular-nums">
                                            v{version.label}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {version.is_current
                                                ? 'Current'
                                                : timeAgo(version.saved_at)}
                                        </span>
                                    </span>
                                    {version.change_note && (
                                        <span className="mt-0.5 line-clamp-2 block text-xs text-muted-foreground">
                                            {version.change_note}
                                        </span>
                                    )}
                                </Link>
                            ))}
                        </nav>

                        <section className="min-w-0 space-y-4">
                            <div className="flex flex-wrap items-center gap-3">
                                <div className="min-w-0">
                                    <h2 className="font-medium">
                                        Version {selected.label}
                                        {selected.is_current && ' (current)'}
                                    </h2>
                                    <p className="text-[13px] text-muted-foreground">
                                        {[
                                            selected.edited_by_name &&
                                                `Saved by ${selected.edited_by_name}`,
                                            timeAgo(selected.saved_at),
                                        ]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </p>
                                </div>
                                <div className="ml-auto flex items-center gap-2">
                                    <ToggleGroup
                                        type="single"
                                        variant="outline"
                                        size="sm"
                                        value={view}
                                        onValueChange={(value) =>
                                            (value === 'changes' ||
                                                value === 'article') &&
                                            setView(value)
                                        }
                                    >
                                        <ToggleGroupItem value="changes">
                                            Changes
                                        </ToggleGroupItem>
                                        <ToggleGroupItem value="article">
                                            Article
                                        </ToggleGroupItem>
                                    </ToggleGroup>
                                    {selected.id &&
                                        can.update &&
                                        !article.is_archived && (
                                            <Form
                                                {...restore.form([
                                                    article.id,
                                                    selected.id,
                                                ])}
                                            >
                                                {({ processing }) => (
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        disabled={processing}
                                                    >
                                                        <RotateCcw />
                                                        Bring back
                                                    </Button>
                                                )}
                                            </Form>
                                        )}
                                </div>
                            </div>

                            {selected.change_note && (
                                <p className="rounded-md border bg-card px-3 py-2 text-sm">
                                    {selected.change_note}
                                </p>
                            )}

                            {view === 'changes' ? (
                                <Changes
                                    selected={selected}
                                    previous={previous}
                                />
                            ) : (
                                <div className="space-y-3">
                                    <h3 className="text-[19px] font-semibold">
                                        {selected.title}
                                    </h3>
                                    {selected.summary && (
                                        <p className="text-sm text-muted-foreground">
                                            {selected.summary}
                                        </p>
                                    )}
                                    <ArticleBody html={selected.body} />
                                </div>
                            )}
                        </section>
                    </div>
                )}
            </div>
        </>
    );
}
