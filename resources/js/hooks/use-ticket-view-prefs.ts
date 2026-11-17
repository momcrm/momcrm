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
    /** Ids of the custom fields shown as columns; none until picked. */
    shownCustomFields: string[];
    toggleCustomField: (fieldId: string) => void;
};

const STORAGE_KEY = 'tickets.view-prefs';

type Stored = {
    groupBy: TicketGroupBy;
    hiddenColumns: TicketColumnKey[];
    shownCustomFields: string[];
};

const defaults: Stored = {
    groupBy: 'status',
    hiddenColumns: ['type'],
    shownCustomFields: [],
};

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
 * it hides or adds. A per-viewer convenience, so it lives in local storage.
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
        shownCustomFields: prefs.shownCustomFields,
        toggleCustomField: (fieldId) =>
            update({
                shownCustomFields: prefs.shownCustomFields.includes(fieldId)
                    ? prefs.shownCustomFields.filter((id) => id !== fieldId)
                    : [...prefs.shownCustomFields, fieldId],
            }),
    };
}
