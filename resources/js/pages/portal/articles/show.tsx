import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import ArticleBody from '@/components/articles/article-body';
import { timeAgo } from '@/lib/format';
import { index } from '@/routes/portal/articles';

/**
 * One knowledge-base article.
 */
export default function PortalArticle({
    article,
}: {
    article: {
        id: string;
        title: string;
        summary: string | null;
        body: string;
        updated_at: string | null;
    };
}) {
    return (
        <>
            <Head title={article.title} />

            <article className="max-w-3xl space-y-4">
                <Link
                    href={index()}
                    className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="size-4" />
                    Knowledge base
                </Link>
                <header className="space-y-1">
                    <h1 className="text-2xl font-semibold">{article.title}</h1>
                    {article.summary && (
                        <p className="text-muted-foreground">
                            {article.summary}
                        </p>
                    )}
                    <p className="text-xs text-muted-foreground">
                        Updated {timeAgo(article.updated_at)}
                    </p>
                </header>
                <ArticleBody html={article.body} />
            </article>
        </>
    );
}
