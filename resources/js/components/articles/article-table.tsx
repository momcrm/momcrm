import { router } from '@inertiajs/react';
import { useMemo } from 'react';
import {
    ArticleStatusPill,
    ClassificationBadge,
} from '@/components/articles/article-badges';
import DataTable, {
    dataTableColumns,
    withoutColumn,
} from '@/components/data-table';
import { LabelChips } from '@/components/labels';
import SoftBadge from '@/components/soft-badge';
import type { DataTableSort } from '@/components/data-table';
import { timeAgo } from '@/lib/format';
import { show } from '@/routes/articles';
import type { ArticleCategory, ArticleListItem } from '@/types';

const column = dataTableColumns<ArticleListItem>();

function buildColumns(categoryPaths: Map<string, string>) {
    return column.columns([
        column.accessor('title', {
            header: 'Title',
            enableSorting: true,
            cell: (info) => (
                <span className="flex min-w-0 flex-col">
                    <span className="flex min-w-0 items-center gap-2">
                        <span className="truncate font-medium">
                            {info.getValue()}
                        </span>
                        <ClassificationBadge
                            classification={info.row.original.classification}
                        />
                    </span>
                    {info.row.original.summary && (
                        <span className="truncate text-xs text-muted-foreground">
                            {info.row.original.summary}
                        </span>
                    )}
                    <LabelChips
                        labels={info.row.original.labels}
                        className="mt-1"
                    />
                </span>
            ),
        }),
        column.accessor(
            (article) =>
                article.category_id
                    ? categoryPaths.get(article.category_id)
                    : undefined,
            {
                id: 'category',
                header: 'Category',
                meta: { className: 'hidden lg:table-cell w-48' },
                cell: (info) => (
                    <span className="truncate text-muted-foreground">
                        {info.getValue() ?? '—'}
                    </span>
                ),
            },
        ),
        column.accessor('status', {
            header: 'Status',
            meta: { className: 'w-28' },
            cell: (info) => (
                <span className="flex flex-col items-start gap-1">
                    <ArticleStatusPill
                        status={info.getValue()}
                        isArchived={info.row.original.is_archived}
                    />
                    {info.row.original.is_review_due && (
                        <SoftBadge tone="warning">Review due</SoftBadge>
                    )}
                </span>
            ),
        }),
        column.accessor('author_name', {
            header: 'Author',
            meta: { className: 'hidden md:table-cell w-40' },
            cell: (info) => (
                <span className="truncate text-muted-foreground">
                    {info.getValue() ?? '—'}
                </span>
            ),
        }),
        column.accessor('updated_at', {
            id: 'updated',
            header: 'Updated',
            enableSorting: true,
            meta: { className: 'hidden sm:table-cell w-32' },
            cell: (info) => (
                <span className="text-muted-foreground">
                    {timeAgo(info.getValue())}
                </span>
            ),
        }),
    ]);
}

/**
 * Articles in a dense table; a row opens the article.
 */
export default function ArticleTable({
    articles,
    categories,
    showsCustomer = true,
    sort,
    onSortChange,
}: {
    articles: ArticleListItem[];
    categories: ArticleCategory[];
    /** Off on a customer's own tab. */
    showsCustomer?: boolean;
    sort: DataTableSort;
    onSortChange: (sort: DataTableSort) => void;
}) {
    const columns = useMemo(() => {
        const all = buildColumns(
            new Map(categories.map((category) => [category.id, category.path])),
        );

        return showsCustomer ? all : withoutColumn(all, 'customer');
    }, [categories, showsCustomer]);

    return (
        <DataTable
            columns={columns}
            data={articles}
            getRowId={(article) => article.id}
            sort={sort}
            onSortChange={onSortChange}
            onRowClick={(article) => router.visit(show.url(article.id))}
        />
    );
}
