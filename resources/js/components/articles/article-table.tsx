import { router } from '@inertiajs/react';
import {
    ArticleStatusPill,
    ClassificationBadge,
} from '@/components/articles/article-badges';
import DataTable, { dataTableColumns } from '@/components/data-table';
import type { DataTableSort } from '@/components/data-table';
import { timeAgo } from '@/lib/format';
import { show } from '@/routes/articles';
import type { ArticleListItem } from '@/types';

const column = dataTableColumns<ArticleListItem>();

const columns = column.columns([
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
            </span>
        ),
    }),
    column.accessor('status', {
        header: 'Status',
        meta: { className: 'w-28' },
        cell: (info) => (
            <ArticleStatusPill
                status={info.getValue()}
                isArchived={info.row.original.is_archived}
            />
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

/**
 * Articles in a dense table; a row opens the article.
 */
export default function ArticleTable({
    articles,
    sort,
    onSortChange,
}: {
    articles: ArticleListItem[];
    sort: DataTableSort;
    onSortChange: (sort: DataTableSort) => void;
}) {
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
