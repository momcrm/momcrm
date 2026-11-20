import { Head, Link, router } from '@inertiajs/react';
import { Send } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';
import EmptyState from '@/components/empty-state';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { SelectCheckbox } from '@/components/tickets/ticket-bulk';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { visitWithQuery } from '@/lib/query';
import { formatCents } from '@/lib/money';
import { index, run, sendMany } from '@/routes/invoices';
import { store } from '@/routes/invoices/run';
import type { BillingRunCustomer } from '@/types';

const dateFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
});

/** Every line of every customer, ticked. */
function allTicked(customers: BillingRunCustomer[]): Record<string, string[]> {
    return Object.fromEntries(
        customers.map((customer) => [
            customer.id,
            customer.lines.map((line) => line.key),
        ]),
    );
}

/**
 * The billing run, as Amphora's finance billing does it: every customer the
 * monthly run would bill on a day, with the lines they are due, all ticked.
 * Untick customers or lines, draft their invoices, then send the drafts.
 */
export default function BillingRun({
    date,
    customers,
}: {
    date: string;
    customers: BillingRunCustomer[];
}) {
    const [ticked, setTicked] = useState(() => allTicked(customers));
    const [drafted, setDrafted] = useState<string[]>([]);
    const [error, setError] = useState<string | undefined>();
    const [processing, setProcessing] = useState(false);
    const [sending, setSending] = useState(false);
    const key = customers
        .map((customer) => customer.lines.map((line) => line.key).join(','))
        .join(';');

    useEffect(() => {
        setTicked(allTicked(customers));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [key]);

    const picked = useMemo(
        () =>
            Object.fromEntries(
                Object.entries(ticked).filter(([, keys]) => keys.length > 0),
            ),
        [ticked],
    );
    const pickedCount = Object.keys(picked).length;

    const toggleLine = (customerId: string, lineKey: string, isOn: boolean) =>
        setTicked((current) => ({
            ...current,
            [customerId]: isOn
                ? [...(current[customerId] ?? []), lineKey]
                : (current[customerId] ?? []).filter(
                      (item) => item !== lineKey,
                  ),
        }));

    const toggleCustomer = (customer: BillingRunCustomer, isOn: boolean) =>
        setTicked((current) => ({
            ...current,
            [customer.id]: isOn ? customer.lines.map((line) => line.key) : [],
        }));

    const draft = () =>
        router.post(
            store.url(),
            { date, customers: picked },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (errors) => setError(Object.values(errors)[0]),
                onFlash: (flash) => {
                    const ids = flash.draftedInvoiceIds as string[] | undefined;

                    if (ids) {
                        setDrafted(ids);
                        setError(undefined);
                    }
                },
            },
        );

    const sendAll = () =>
        router.post(
            sendMany.url(),
            { invoice_ids: drafted },
            {
                preserveScroll: true,
                onStart: () => setSending(true),
                onFinish: () => setSending(false),
                onSuccess: () => setDrafted([]),
                onError: (errors) => {
                    const message = Object.values(errors)[0];

                    if (message) {
                        toast.error(message);
                    }
                },
            },
        );

    return (
        <>
            <Head title="Billing run" />

            <div className="flex flex-col gap-6 p-4 pb-0 md:p-6 md:pb-0">
                <PageHeader
                    title="Billing run"
                    description="What every billable customer is due: service periods starting by the day below, and waiting one-off charges. The monthly run does this on the 2nd; run it here to check first, or to bill on another day."
                />

                <div className="flex flex-wrap items-end gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="run-day">
                            Bill periods starting by
                        </Label>
                        <Input
                            id="run-day"
                            type="date"
                            value={date}
                            onChange={(event) =>
                                event.target.value &&
                                visitWithQuery(
                                    { date: event.target.value },
                                    { only: ['date', 'customers'] },
                                )
                            }
                            className="w-auto"
                        />
                    </div>
                </div>

                {drafted.length > 0 && (
                    <div className="flex flex-wrap items-center gap-3 rounded-lg border border-l-[3px] border-l-primary bg-card px-4 py-3 text-sm">
                        <span className="mr-auto">
                            {drafted.length === 1
                                ? '1 invoice drafted.'
                                : `${drafted.length} invoices drafted.`}{' '}
                            Send them to each customer's contacts who get
                            invoices, or check them first.
                        </span>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={index({ query: { status: 'draft' } })}>
                                View drafts
                            </Link>
                        </Button>
                        <Button size="sm" disabled={sending} onClick={sendAll}>
                            {sending ? <Spinner /> : <Send />}
                            Send all
                        </Button>
                    </div>
                )}

                {customers.length === 0 ? (
                    <EmptyState
                        title="Nobody to bill"
                        description="Every billable customer's service periods starting by that day are on an invoice already, and there are no waiting charges."
                    />
                ) : (
                    <div className="space-y-3">
                        {customers.map((customer) => {
                            const keys = ticked[customer.id] ?? [];
                            const total = customer.lines
                                .filter((line) => keys.includes(line.key))
                                .reduce(
                                    (sum, line) => sum + line.total_in_cents,
                                    0,
                                );

                            return (
                                <section
                                    key={customer.id}
                                    className="rounded-lg border bg-card shadow-xs"
                                >
                                    <div className="flex items-center gap-3 border-b px-4 py-2.5">
                                        <SelectCheckbox
                                            label={`Bill ${customer.name}`}
                                            checked={
                                                keys.length ===
                                                customer.lines.length
                                            }
                                            indeterminate={keys.length > 0}
                                            onChange={(isOn) =>
                                                toggleCustomer(customer, isOn)
                                            }
                                        />
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium">
                                                {customer.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Due{' '}
                                                {dateFormatter.format(
                                                    new Date(customer.due_on),
                                                )}
                                            </p>
                                        </div>
                                        <span className="text-sm font-medium tabular-nums">
                                            {formatCents(
                                                total,
                                                customer.currency,
                                            )}
                                        </span>
                                    </div>
                                    <ul className="divide-y">
                                        {customer.lines.map((line) => (
                                            <li
                                                key={line.key}
                                                className="flex items-center gap-3 px-4 py-1.5 text-sm"
                                            >
                                                <Checkbox
                                                    id={`run-${line.key}`}
                                                    checked={keys.includes(
                                                        line.key,
                                                    )}
                                                    onCheckedChange={(isOn) =>
                                                        toggleLine(
                                                            customer.id,
                                                            line.key,
                                                            isOn === true,
                                                        )
                                                    }
                                                />
                                                <Label
                                                    htmlFor={`run-${line.key}`}
                                                    className="min-w-0 flex-1 truncate font-normal"
                                                >
                                                    {line.description}
                                                    {line.quantity > 1 &&
                                                        ` × ${line.quantity}`}
                                                </Label>
                                                <span className="text-muted-foreground tabular-nums">
                                                    {formatCents(
                                                        line.total_in_cents,
                                                        customer.currency,
                                                    )}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            );
                        })}
                    </div>
                )}

                {customers.length > 0 && (
                    <div className="sticky bottom-0 z-30 -mx-4 border-t bg-background/95 px-4 py-3 backdrop-blur md:-mx-6 md:px-6">
                        <div className="flex items-center gap-3">
                            <span className="mr-auto text-sm text-muted-foreground">
                                {pickedCount} of {customers.length} customers,
                                before tax
                            </span>
                            <InputError message={error} />
                            <Button
                                disabled={processing || pickedCount === 0}
                                onClick={draft}
                            >
                                {processing && <Spinner />}
                                Draft{' '}
                                {pickedCount === 1
                                    ? '1 invoice'
                                    : `${pickedCount} invoices`}
                            </Button>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

BillingRun.layout = {
    breadcrumbs: [
        { title: 'Invoices', href: index() },
        { title: 'Billing run', href: run() },
    ],
};
