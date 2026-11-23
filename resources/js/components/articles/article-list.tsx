import { Link } from '@inertiajs/react';
import { Building2, FileText, Folder } from 'lucide-react';
import type { ReactNode } from 'react';
import {
    ArticleStatusPill,
    ClassificationBadge,
} from '@/components/articles/article-badges';
import { LabelChips } from '@/components/labels';
import SoftBadge from '@/components/soft-badge';
import { timeAgo } from '@/lib/format';
import { cn } from '@/lib/utils';
import { show } from '@/routes/articles';
import type { ArticleListItem } from '@/types';

/**
 * Articles as rows to read down: title, summary and where each one sits.
 * A row opens the article.
 */
export default function ArticleList({
    articles,
    categoryPaths,
    header,
}: {
    articles: ArticleListItem[];
    /** Each category's full path, such as "Networking › Routers". */
    categoryPaths: Map<string, string>;
    header?: ReactNode;
}) {
    return (
        <section className="overflow-hidden rounded-xl border bg-card shadow-xs">
            {header && (
                <div className="flex min-h-11 flex-wrap items-center justify-between gap-2 border-b px-4 py-1.5 sm:px-5">
                    {header}
                </div>
            )}
            <ul className="divide-y">
                {articles.map((article) => (
                    <li key={article.id}>
                        <ArticleRow
                            article={article}
                            categoryPath={
                                article.category_id
                                    ? categoryPaths.get(article.category_id)
                                    : undefined
                            }
                        />
                    </li>
                ))}
            </ul>
        </section>
    );
}

function ArticleRow({
    article,
    categoryPath,
}: {
    article: ArticleListItem;
    categoryPath?: string;
}) {
    const isMuted = article.is_archived;

    return (
        <Link
            href={show.url(article.id)}
            className="group flex gap-3.5 px-4 py-4 transition-colors outline-none hover:bg-muted/40 focus-visible:bg-muted/50 sm:px-5"
        >
            <span
                aria-hidden
                className={cn(
                    'mt-0.5 hidden size-9 shrink-0 items-center justify-center rounded-lg border bg-background text-muted-foreground transition-colors group-hover:border-primary/30 group-hover:text-primary sm:flex',
                    isMuted && 'opacity-60',
                )}
            >
                <FileText className="size-4" />
            </span>

            <span className="min-w-0 flex-1">
                <span className="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
                    <span
                        className={cn(
                            'min-w-0 text-[14.5px] font-medium tracking-[-0.006em] break-words group-hover:text-primary',
                            isMuted && 'text-muted-foreground',
                        )}
                    >
                        {article.title}
                    </span>
                    <ClassificationBadge
                        classification={article.classification}
                    />
                </span>

                {article.summary && (
                    <span className="mt-0.5 line-clamp-2 text-[13px] leading-relaxed text-soft-foreground">
                        {article.summary}
                    </span>
                )}

                <span className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs text-muted-foreground">
                    {categoryPath && (
                        <Meta icon={<Folder />}>{categoryPath}</Meta>
                    )}
                    {article.customer_name && (
                        <Meta icon={<Building2 />}>
                            {article.customer_name}
                        </Meta>
                    )}
                    <span>
                        {[
                            article.author_name,
                            article.updated_at &&
                                `Updated ${timeAgo(article.updated_at)}`,
                            article.version && `v${article.version}`,
                        ]
                            .filter(Boolean)
                            .join(' · ')}
                    </span>
                    <LabelChips labels={article.labels} />
                </span>
            </span>

            <span className="flex shrink-0 flex-col items-end gap-1.5">
                <ArticleStatusPill
                    status={article.status}
                    isArchived={article.is_archived}
                />
                {article.is_review_due && !article.is_archived && (
                    <SoftBadge tone="warning">Review due</SoftBadge>
                )}
            </span>
        </Link>
    );
}

function Meta({ icon, children }: { icon: ReactNode; children: ReactNode }) {
    return (
        <span className="inline-flex min-w-0 items-center gap-1 [&_svg]:size-3.5 [&_svg]:shrink-0 [&_svg]:text-subtle-foreground">
            {icon}
            <span className="truncate">{children}</span>
        </span>
    );
}
