import { useCallback, useState } from 'react';

export type TicketGroupBy =
    | 'status'
    | 'priority'
    | 'assignee'
    | 'project'
    | 'none';

export type TicketColumnKey =
    | 'status'
    | 'priority'
    | 'assignee'
    | 'due_on'
    | 'labels'
    | 'estimate'
    | 'project'
    | 'type';

export type TicketViewPrefs = {
    groupBy: TicketGroupBy;
    setGroupBy: (groupBy: TicketGroupBy) => void;
    hiddenColumns: TicketColumnKey[];
    toggleColumn: (column: TicketColumnKey) => void;
};

const STORAGE_KEY = 'tickets.view-prefs';

type Stored = { groupBy: TicketGroupBy; hiddenColumns: TicketColumnKey[] };

const defaults: Stored = { groupBy: 'status', hiddenColumns: ['type'] };

function read(): Stored {
    try {
        const stored = JSON.parse(
            window.localStorage.getItem(STORAGE_KEY) ?? 'null',
        ) as Partial<Stored> | null;

        return { ...defaults, ...stored };
    } catch {
        return defaults;
    }
}

function write(prefs: Stored): void {
    try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
    } catch {
        // Private windows can refuse storage; the choice just won't stick.
    }
}

/**
 * How this browser groups the ticket list and table and which table columns
 * it hides. A per-viewer convenience, so it lives in local storage.
 */
export function useTicketViewPrefs(): TicketViewPrefs {
    const [prefs, setPrefs] = useState<Stored>(read);

    const update = useCallback((changes: Partial<Stored>) => {
        setPrefs((current) => {
            const next = { ...current, ...changes };
            write(next);

            return next;
        });
    }, []);

    return {
        groupBy: prefs.groupBy,
        setGroupBy: (groupBy) => update({ groupBy }),
        hiddenColumns: prefs.hiddenColumns,
        toggleColumn: (column) =>
            update({
                hiddenColumns: prefs.hiddenColumns.includes(column)
                    ? prefs.hiddenColumns.filter((key) => key !== column)
                    : [...prefs.hiddenColumns, column],
            }),
    };
}
