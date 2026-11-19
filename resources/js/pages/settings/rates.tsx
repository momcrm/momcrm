import { Head, router } from '@inertiajs/react';
import { Archive, ArchiveRestore, Pencil, Plus } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { billingPeriodLabel } from '@/components/rates/billing-period';
import RateFormDialog from '@/components/rates/rate-form-dialog';
import { Button } from '@/components/ui/button';
import { formatCents } from '@/lib/money';
import { archive, restore } from '@/routes/rates';
import type { Rate } from '@/types';

/**
 * "$95.00 a month", and how often it is billed when that isn't monthly.
 */
function describePrice(rate: Rate): string {
    const price = `${formatCents(rate.price_in_cents)} a month`;

    return rate.billing_period_in_months === 1
        ? price
        : `${price}, billed every ${billingPeriodLabel(rate.billing_period_in_months)}`;
}

function RateRow({
    rate,
    onEdit,
}: {
    rate: Rate;
    onEdit: (rate: Rate) => void;
}) {
    return (
        <li className="flex items-center gap-2 px-3 py-2">
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
                    {[describePrice(rate), rate.description]
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
}: {
    rates: Rate[];
    billingPeriods: number[];
}) {
    const [editing, setEditing] = useState<Rate | 'new' | null>(null);
    const live = rates.filter((rate) => !rate.is_archived);
    const archived = rates.filter((rate) => rate.is_archived);

    return (
        <>
            <Head title="Rates" />

            <h1 className="sr-only">Rates</h1>

            <div className="space-y-6">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        variant="small"
                        title="Rates"
                        description="Your price list. Each service is priced from a rate, so changing a rate changes what its services are billed from their next invoice."
                    />
                    <Button
                        size="sm"
                        className="shrink-0"
                        onClick={() => setEditing('new')}
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
                                onEdit={setEditing}
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
                                    onEdit={setEditing}
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
                        : editing === 'new'
                          ? 'new'
                          : editing.id
                }
                open={editing !== null}
                onOpenChange={(open) => !open && setEditing(null)}
                billingPeriods={billingPeriods}
                rate={
                    editing === null || editing === 'new' ? undefined : editing
                }
            />
        </>
    );
}
