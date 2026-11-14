import {
    createColumnHelper,
    rowSortingFeature,
    tableFeatures,
    useTable,
} from '@tanstack/react-table';
import type {
    CellData,
    ColumnDef,
    RowData,
    SortingState,
    TableFeatures,
    Updater,
} from '@tanstack/react-table';
import { ArrowDown, ArrowUp, ChevronsUpDown } from 'lucide-react';
import { useMemo } from 'react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

declare module '@tanstack/react-table' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface ColumnMeta<
        in out TFeatures extends TableFeatures,
        in out TData extends RowData,
        TValue extends CellData = CellData,
    > {
        /** Classes for this column's cells, e.g. a width or alignment. */
        className?: string;
    }
}

/**
 * The features every data table registers. Sorting is manual: the server sorts
 * and the table only shows and changes the sort.
 */
export const dataTableFeatures = tableFeatures({ rowSortingFeature });

export type DataTableFeatures = typeof dataTableFeatures;

/**
 * A typed column helper for a data table's rows.
 */
export function dataTableColumns<TRow extends RowData>() {
    return createColumnHelper<DataTableFeatures, TRow>();
}

export type DataTableSort = { column: string; direction: 'asc' | 'desc' };

/**
 * The same columns without the one for `key`, e.g. a customer column on that
 * customer's own page.
 */
export function withoutColumn<TColumn extends { id?: string }>(
    columns: TColumn[],
    key: string,
): TColumn[] {
    return columns.filter(
        (column) =>
            column.id !== key &&
            (column as { accessorKey?: string }).accessorKey !== key,
    );
}

const defaultColumn = { enableSorting: false };

/**
 * The app's dense data table on TanStack Table. Rows link through `onRowClick`;
 * sortable columns (enableSorting: true) report changes via `onSortChange` so
 * the page can reload sorted data.
 */
export default function DataTable<TRow extends RowData>({
    columns,
    data,
    getRowId,
    sort,
    onSortChange,
    onRowClick,
    empty,
    className,
}: {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    columns: ColumnDef<DataTableFeatures, TRow, any>[];
    data: TRow[];
    getRowId: (row: TRow) => string;
    sort?: DataTableSort;
    onSortChange?: (sort: DataTableSort) => void;
    onRowClick?: (row: TRow) => void;
    empty?: ReactNode;
    className?: string;
}) {
    const sorting = useMemo<SortingState>(
        () =>
            sort ? [{ id: sort.column, desc: sort.direction === 'desc' }] : [],
        [sort],
    );

    const table = useTable({
        features: dataTableFeatures,
        columns,
        data,
        defaultColumn,
        getRowId: (row) => getRowId(row),
        manualSorting: true,
        enableSorting: onSortChange !== undefined,
        enableSortingRemoval: false,
        state: { sorting },
        onSortingChange: (updater: Updater<SortingState>) => {
            const next =
                typeof updater === 'function' ? updater(sorting) : updater;
            const first = next[0];

            if (first && onSortChange) {
                onSortChange({
                    column: first.id,
                    direction: first.desc ? 'desc' : 'asc',
                });
            }
        },
    });

    const rows = table.getRowModel().rows;

    return (
        <div
            className={cn(
                'overflow-x-auto rounded-lg border bg-card shadow-xs',
                className,
            )}
        >
            <table className="w-full text-sm">
                <thead>
                    {table.getHeaderGroups().map((group) => (
                        <tr key={group.id} className="border-b bg-muted/60">
                            {group.headers.map((header) => {
                                const direction = header.column.getIsSorted();
                                const canSort = header.column.getCanSort();

                                return (
                                    <th
                                        key={header.id}
                                        scope="col"
                                        aria-sort={
                                            direction === 'asc'
                                                ? 'ascending'
                                                : direction === 'desc'
                                                  ? 'descending'
                                                  : undefined
                                        }
                                        className={cn(
                                            'h-8 px-3 text-left text-xs font-medium whitespace-nowrap text-muted-foreground',
                                            header.column.columnDef.meta
                                                ?.className,
                                        )}
                                    >
                                        {header.isPlaceholder ? null : canSort ? (
                                            <button
                                                type="button"
                                                onClick={header.column.getToggleSortingHandler()}
                                                className="-mx-1 inline-flex items-center gap-1 rounded px-1 hover:text-foreground"
                                            >
                                                <table.FlexRender
                                                    header={header}
                                                />
                                                {direction === 'asc' ? (
                                                    <ArrowUp className="size-3" />
                                                ) : direction === 'desc' ? (
                                                    <ArrowDown className="size-3" />
                                                ) : (
                                                    <ChevronsUpDown className="size-3 opacity-50" />
                                                )}
                                            </button>
                                        ) : (
                                            <table.FlexRender header={header} />
                                        )}
                                    </th>
                                );
                            })}
                        </tr>
                    ))}
                </thead>
                <tbody>
                    {rows.length === 0 && empty ? (
                        <tr>
                            <td colSpan={columns.length}>{empty}</td>
                        </tr>
                    ) : (
                        rows.map((row) => (
                            <tr
                                key={row.id}
                                onClick={
                                    onRowClick
                                        ? () => onRowClick(row.original)
                                        : undefined
                                }
                                className={cn(
                                    'border-b last:border-b-0',
                                    onRowClick &&
                                        'cursor-pointer hover:bg-accent/50',
                                )}
                            >
                                {row.getAllCells().map((cell) => (
                                    <td
                                        key={cell.id}
                                        className={cn(
                                            'h-10 px-3',
                                            cell.column.columnDef.meta
                                                ?.className,
                                        )}
                                    >
                                        <table.FlexRender cell={cell} />
                                    </td>
                                ))}
                            </tr>
                        ))
                    )}
                </tbody>
            </table>
        </div>
    );
}
