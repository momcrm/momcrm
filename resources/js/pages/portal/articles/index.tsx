import { Head, Link, router } from '@inertiajs/react';
import { BookOpen } from 'lucide-react';
import { useState } from 'react';
import EmptyState from '@/components/empty-state';
import SoftBadge from '@/components/soft-badge';
import { Input } from '@/components/ui/input';
import { index, show } from '@/routes/articles';
import type { PortalArticleItem } from '@/types';

/**
 * The knowledge base: answers and how-tos written for customers.
 */
export default function PortalArticles({
    articles,
    search,
}: {
    articles: PortalArticleItem[];
    search: string | null;
}) {
    const [term, setTerm] = useState(search ?? '');

    return (
        <>
            <Head title="Knowledge base" />

            <div className="flex flex-wrap items-center justify-between gap-3">
                <h1 className="text-xl font-semibold">Knowledge base</h1>
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.get(
                            index.url({
                                query: term.trim()
                                    ? { search: term.trim() }
                                    : {},
                            }),
                        );
                    }}
                >
                    <Input
                        type="search"
                        aria-label="Search articles"
                        placeholder="Search articles…"
                        value={term}
                        onChange={(event) => setTerm(event.target.value)}
                        className="h-8 w-64"
                    />
                </form>
            </div>

            {articles.length === 0 ? (
                <EmptyState
                    icon={BookOpen}
                    title={search ? 'No articles match' : 'No articles yet'}
                    description={
                        search
                            ? 'Try other words, or raise a ticket and we’ll help.'
                            : 'Answers and how-tos we write for you show here.'
                    }
                />
            ) : (
                <ul className="divide-y overflow-hidden rounded-lg border bg-card">
                    {articles.map((article) => (
                        <li key={article.id}>
                            <Link
                                href={show(article.id)}
                                className="block px-4 py-3 hover:bg-accent"
                            >
                                <span className="flex items-center gap-2 text-sm font-medium">
                                    {article.title}
                                    {article.is_for_customer && (
                                        <SoftBadge tone="info">
                                            For you
                                        </SoftBadge>
                                    )}
                                </span>
                                {article.summary && (
                                    <span className="mt-0.5 block text-sm text-muted-foreground">
                                        {article.summary}
                                    </span>
                                )}
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </>
    );
}
