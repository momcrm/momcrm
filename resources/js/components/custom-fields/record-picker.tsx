import { useHttp } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { records as recordsRoute } from '@/routes/custom-fields';
import type { CustomField, Option } from '@/types';

/**
 * Picks the record a record-link field points at. The team's records are
 * looked up as the person types, since there can be thousands of them.
 */
export default function RecordPicker({
    field,
    value,
    label,
    onChange,
    className,
    id,
}: {
    field: CustomField;
    value: string | null;
    /** The linked record's name, when the page knows it. */
    label?: string;
    onChange: (value: string | null) => void;
    className?: string;
    id?: string;
}) {
    const [isOpen, setIsOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [records, setRecords] = useState<Option[] | null>(null);
    const [picked, setPicked] = useState<Option | null>(null);
    const filterInput = useRef<HTMLInputElement>(null);
    const http = useHttp<Record<string, never>, { records: Option[] }>();
    const name = picked?.value === value ? picked.label : label;

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        const timer = window.setTimeout(
            () => {
                http.cancel();
                void http
                    .get(
                        recordsRoute.url(field.id, {
                            query: { search: query.trim() },
                        }),
                    )
                    .then((response) => setRecords(response.records))
                    .catch(() => undefined);
            },
            query ? 200 : 0,
        );

        return () => window.clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [isOpen, query, field.id]);

    const pick = (record: Option | null) => {
        setPicked(record);
        setIsOpen(false);

        if ((record?.value ?? null) !== value) {
            onChange(record?.value ?? null);
        }
    };

    return (
        <DropdownMenu
            open={isOpen}
            onOpenChange={(open) => {
                setIsOpen(open);

                if (open) {
                    requestAnimationFrame(() => filterInput.current?.focus());
                } else {
                    setQuery('');
                }
            }}
        >
            <DropdownMenuTrigger
                id={id}
                aria-label={field.name}
                className={className}
            >
                <span
                    className={cn(
                        'truncate',
                        !value && 'text-muted-foreground',
                    )}
                >
                    {value
                        ? (name ?? 'A record that no longer exists')
                        : 'None'}
                </span>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                align="start"
                className="max-h-72 w-60 overflow-y-auto"
            >
                <input
                    ref={filterInput}
                    value={query}
                    onChange={(event) => setQuery(event.target.value)}
                    onKeyDown={(event) => {
                        if (event.key === 'Escape') {
                            return;
                        }

                        event.stopPropagation();

                        if (event.key === 'ArrowDown') {
                            event.preventDefault();
                            event.currentTarget
                                .closest('[role="menu"]')
                                ?.querySelector<HTMLElement>(
                                    '[role="menuitem"]',
                                )
                                ?.focus();
                        }

                        if (event.key === 'Enter' && records?.[0]) {
                            event.preventDefault();
                            pick(records[0]);
                        }
                    }}
                    placeholder="Search…"
                    aria-label={`Search ${field.name}`}
                    className="h-8 w-full bg-transparent px-2 text-sm outline-none placeholder:text-subtle-foreground"
                />
                <DropdownMenuSeparator />
                {value && !query && (
                    <DropdownMenuItem onSelect={() => pick(null)}>
                        <span className="text-muted-foreground">None</span>
                    </DropdownMenuItem>
                )}
                {records === null && (
                    <p className="px-2 py-1.5 text-sm text-muted-foreground">
                        Loading…
                    </p>
                )}
                {records?.length === 0 && (
                    <p className="px-2 py-1.5 text-sm text-muted-foreground">
                        No matches.
                    </p>
                )}
                {records?.map((record) => (
                    <DropdownMenuItem
                        key={record.value}
                        onSelect={() => pick(record)}
                    >
                        <span className="truncate">{record.label}</span>
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
