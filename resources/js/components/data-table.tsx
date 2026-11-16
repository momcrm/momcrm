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
import { Fragment, useMemo } from 'react';
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
    groups,
    getGroupKey,
    isRowSelected,
    variant = 'card',
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
    /** Sections of rows, in order, each under a header row. */
    groups?: { key: string; header: ReactNode }[];
    getGroupKey?: (row: TRow) => string;
    /** Highlights rows picked for a bulk change. */
    isRowSelected?: (row: TRow) => boolean;
    /** `flush` runs edge to edge with ruled cells, for a page's main view. */
    variant?: 'card' | 'flush';
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
    const isFlush = variant === 'flush';
    const sections =
        groups && getGroupKey
            ? groups
                  .map((group) => ({
                      ...group,
                      rows: rows.filter(
                          (row) => getGroupKey(row.original) === group.key,
                      ),
                  }))
                  .filter((group) => group.rows.length > 0)
            : [{ key: '', header: null, rows }];

    return (
        <div
            className={cn(
                isFlush
                    ? 'overflow-x-auto bg-card'
                    : 'overflow-x-auto rounded-lg border bg-card shadow-xs',
                className,
            )}
        >
            <table
                className={cn(
                    'w-full',
                    isFlush
                        ? 'min-w-[1080px] table-fixed border-separate border-spacing-0 text-[12.5px]'
                        : 'text-sm',
                )}
            >
                <thead>
                    {table.getHeaderGroups().map((group) => (
                        <tr
                            key={group.id}
                            className={cn(!isFlush && 'border-b bg-muted/60')}
                        >
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
                                            isFlush
                                                ? 'sticky top-0 z-[3] h-[34px] border-r border-b border-r-divider bg-card px-2.5 text-left text-xs font-medium whitespace-nowrap text-soft-foreground select-none first:pl-4 last:border-r-0 md:first:pl-6'
                                                : 'h-8 px-3 text-left text-xs font-medium whitespace-nowrap text-muted-foreground',
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
                                                ) : isFlush ? null : (
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
                        sections.map((section) => (
                            <Fragment key={section.key}>
                                {section.header !== null && (
                                    <tr>
                                        <td
                                            colSpan={columns.length}
                                            className={cn(
                                                isFlush
                                                    ? 'h-8 border-b border-divider bg-sunken px-4 text-xs font-semibold text-soft-foreground md:px-6'
                                                    : 'h-9 border-b bg-muted/60 px-3 text-xs font-medium',
                                            )}
                                        >
                                            {section.header}
                                        </td>
                                    </tr>
                                )}
                                {section.rows.map((row) => (
                                    <tr
                                        key={row.id}
                                        onClick={
                                            onRowClick
                                                ? () => onRowClick(row.original)
                                                : undefined
                                        }
                                        className={cn(
                                            isFlush
                                                ? 'group/row'
                                                : 'border-b last:border-b-0',
                                            onRowClick &&
                                                (isFlush
                                                    ? 'cursor-pointer'
                                                    : 'cursor-pointer hover:bg-accent/50'),
                                        )}
                                    >
                                        {row.getAllCells().map((cell) => (
                                            <td
                                                key={cell.id}
                                                className={cn(
                                                    isFlush
                                                        ? cn(
                                                              'h-9 truncate border-r border-b border-divider px-2.5 whitespace-nowrap first:pl-4 last:border-r-0 md:first:pl-6',
                                                              isRowSelected?.(
                                                                  row.original,
                                                              )
                                                                  ? 'bg-[color-mix(in_srgb,var(--primary)_10%,var(--card))]'
                                                                  : 'bg-card group-hover/row:bg-muted',
                                                          )
                                                        : 'h-10 px-3',
                                                    cell.column.columnDef.meta
                                                        ?.className,
                                                )}
                                            >
                                                <table.FlexRender cell={cell} />
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </Fragment>
                        ))
                    )}
                </tbody>
            </table>
        </div>
    );
}
