import { Head, router } from '@inertiajs/react';
import {
    Archive,
    ArchiveRestore,
    CornerDownRight,
    Pencil,
    Plus,
} from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { billingPeriodLabel } from '@/components/rates/billing-period';
import RateFormDialog from '@/components/rates/rate-form-dialog';
import { depthAmong } from '@/components/rates/rate-tree';
import { Button } from '@/components/ui/button';
import { formatPrices } from '@/lib/money';
import { archive, restore } from '@/routes/rates';
import type { Option, Rate } from '@/types';

/**
 * "$95.00 · USD 62.00 a month", and how often it is billed when that isn't
 * monthly.
 */
function describePrice(rate: Rate): string {
    const price = `${formatPrices(rate.prices)} a month`;

    return rate.billing_period_in_months === 1
        ? price
        : `${price}, billed every ${billingPeriodLabel(rate.billing_period_in_months)}`;
}

type Editing = { rate: Rate } | { parentId: string | null } | null;

function RateRow({
    rate,
    depth,
    note,
    onEdit,
    onAddUnder,
}: {
    rate: Rate;
    /** How far to indent it under the rates shown above it. */
    depth: number;
    /** Where it sits, when its parent isn't shown beside it. */
    note?: string;
    onEdit: (rate: Rate) => void;
    onAddUnder: (rate: Rate) => void;
}) {
    return (
        <li
            className="flex items-center gap-2 py-2 pr-3"
            style={{ paddingLeft: `${0.75 + depth * 1.5}rem` }}
        >
            {depth > 0 && (
                <CornerDownRight className="size-3.5 shrink-0 text-subtle-foreground" />
            )}
            <div className="min-w-0 flex-1">
                <p
                    className={
                        rate.is_archived
                            ? 'truncate text-sm text-muted-foreground'
                            : 'truncate text-sm font-medium'
                    }
                >
                    {rate.name}
                </p>
                <p className="truncate text-xs text-muted-foreground">
                    {[note, describePrice(rate), rate.description]
                        .filter(Boolean)
                        .join(' · ')}
                </p>
            </div>
            {rate.is_archived ? (
                <Button
                    variant="ghost"
                    size="sm"
                    className="h-7"
                    onClick={() =>
                        router.post(
                            restore.url(rate.id),
                            {},
                            { preserveScroll: true },
                        )
                    }
                >
                    <ArchiveRestore />
                    Restore
                </Button>
            ) : (
                <>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        aria-label={`Add a rate under ${rate.name}`}
                        title="Add a rate under this one"
                        onClick={() => onAddUnder(rate)}
                    >
                        <Plus />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        aria-label={`Edit ${rate.name}`}
                        onClick={() => onEdit(rate)}
                    >
                        <Pencil />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        aria-label={`Archive ${rate.name}`}
                        title="Archive: no longer offered for new services"
                        onClick={() =>
                            router.post(
                                archive.url(rate.id),
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        <Archive />
                    </Button>
                </>
            )}
        </li>
    );
}

export default function Rates({
    rates,
    billingPeriods,
    currencies,
}: {
    rates: Rate[];
    billingPeriods: number[];
    currencies: Option[];
}) {
    const [editing, setEditing] = useState<Editing>(null);
    const live = rates.filter((rate) => !rate.is_archived);
    const archived = rates.filter((rate) => rate.is_archived);
    const byId = new Map(rates.map((rate) => [rate.id, rate]));

    /** "Under Microsoft 365 (archived)" for a live rate whose parent isn't live. */
    const liveNote = (rate: Rate): string | undefined => {
        const parent = rate.parent_id ? byId.get(rate.parent_id) : undefined;

        return parent?.is_archived
            ? `Under ${parent.path} (archived)`
            : undefined;
    };
    const edit = (rate: Rate) => setEditing({ rate });
    const addUnder = (rate: Rate) => setEditing({ parentId: rate.id });

    return (
        <>
            <Head title="Rates" />

            <h1 className="sr-only">Rates</h1>

            <div className="space-y-6">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        variant="small"
                        title="Rates"
                        description="Your price list. Each service is priced from a rate in its customer's currency, so changing a rate changes what its services are billed from their next invoice. Put rates under others to group them; a rate with rates under it can still be sold."
                    />
                    <Button
                        size="sm"
                        className="shrink-0"
                        onClick={() => setEditing({ parentId: null })}
                    >
                        <Plus />
                        Add rate
                    </Button>
                </div>

                {live.length === 0 ? (
                    <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                        No rates yet. Add one for each thing you sell, such as a
                        support plan or a domain name.
                    </p>
                ) : (
                    <ul className="divide-y rounded-lg border bg-card shadow-xs">
                        {live.map((rate) => (
                            <RateRow
                                key={rate.id}
                                rate={rate}
                                depth={depthAmong(rate, live)}
                                note={liveNote(rate)}
                                onEdit={edit}
                                onAddUnder={addUnder}
                            />
                        ))}
                    </ul>
                )}

                {archived.length > 0 && (
                    <section className="space-y-2">
                        <div>
                            <h2 className="text-sm font-medium">Archived</h2>
                            <p className="text-xs text-muted-foreground">
                                Not offered for new services. Services already
                                on one keep it.
                            </p>
                        </div>
                        <ul className="divide-y rounded-lg border bg-card shadow-xs">
                            {archived.map((rate) => (
                                <RateRow
                                    key={rate.id}
                                    rate={rate}
                                    depth={0}
                                    note={
                                        rate.depth > 0
                                            ? `Under ${rate.path.slice(0, rate.path.lastIndexOf(' › '))}`
                                            : undefined
                                    }
                                    onEdit={edit}
                                    onAddUnder={addUnder}
                                />
                            ))}
                        </ul>
                    </section>
                )}
            </div>

            <RateFormDialog
                key={
                    editing === null
                        ? 'closed'
                        : 'rate' in editing
                          ? editing.rate.id
                          : `new-${editing.parentId ?? 'top'}`
                }
                open={editing !== null}
                onOpenChange={(open) => !open && setEditing(null)}
                billingPeriods={billingPeriods}
                currencies={currencies}
                rates={rates}
                rate={editing && 'rate' in editing ? editing.rate : undefined}
                parentId={
                    editing && 'parentId' in editing
                        ? editing.parentId
                        : undefined
                }
            />
        </>
    );
}
