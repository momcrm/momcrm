import { router, useHttp } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import StorePaymentAllocationsController from '@/actions/App/Payment/Controllers/StorePaymentAllocationsController';
import StorePaymentController from '@/actions/App/Payment/Controllers/StorePaymentController';
import FormField from '@/components/form-field';
import InputError from '@/components/input-error';
import NativeSelect from '@/components/native-select';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatCents } from '@/lib/money';
import { cn } from '@/lib/utils';
import { outstandingInvoices } from '@/routes/customers';
import type {
    BankTransaction,
    InvoiceListItem,
    OutstandingInvoices,
    Payment,
} from '@/types';

const dateFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
});

/** Today as YYYY-MM-DD in the browser's time zone. */
function today(): string {
    const now = new Date();
    const pad = (value: number) => String(value).padStart(2, '0');

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

/** Dollars typed into a field as whole cents, or 0 when it isn't a number. */
function toCents(value: string): number {
    const amount = Number.parseFloat(value);

    return Number.isFinite(amount) ? Math.round(amount * 100) : 0;
}

function toDollars(cents: number): string {
    return (cents / 100).toFixed(2);
}

/**
 * What each ticked invoice gets: the amount typed for it, or otherwise its
 * share of what is left, oldest due first, up to what is owing on it.
 */
function spread(
    invoices: InvoiceListItem[],
    amountInCents: number,
    ticked: Set<string>,
    typed: Record<string, string>,
): Record<string, number> {
    const applied: Record<string, number> = {};
    let left = amountInCents;

    for (const invoice of invoices) {
        if (ticked.has(invoice.id) && typed[invoice.id] !== undefined) {
            applied[invoice.id] = toCents(typed[invoice.id]);
            left -= applied[invoice.id];
        }
    }

    for (const invoice of invoices) {
        if (!ticked.has(invoice.id) || typed[invoice.id] !== undefined) {
            continue;
        }

        applied[invoice.id] = Math.max(
            0,
            Math.min(left, invoice.balance_in_cents),
        );
        left -= applied[invoice.id];
    }

    return applied;
}

/**
 * Records money a customer paid and splits it over their invoices still
 * owing, as Amphora's Pay screen does: the amount spreads over the ticked
 * invoices, oldest due first, and any line can be changed by hand. What
 * isn't applied is kept as their credit. Opened for an invoice, that
 * invoice is ticked for what is owing on it. Given a payment, it applies
 * what is left of that payment instead of recording a new one. Given a bank
 * feed line, it records that deposit: its amount and date are fixed, and the
 * line shows as applied.
 */
export default function RecordPaymentDialog({
    customerId,
    invoiceId,
    payment,
    bankLine,
    open,
    onOpenChange,
    onRecorded,
}: {
    customerId: string;
    /** An invoice to pay off, ticked when the dialog opens. */
    invoiceId?: string;
    /** A payment whose unapplied money to apply. */
    payment?: Payment;
    /** A deposit from the bank feed to record. */
    bankLine?: BankTransaction;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onRecorded?: () => void;
}) {
    const isApplying = payment !== undefined;
    const http = useHttp<Record<string, never>, OutstandingInvoices>();
    const [data, setData] = useState<OutstandingInvoices | null>(null);
    const [amount, setAmount] = useState('');
    const [paidOn, setPaidOn] = useState(today);
    const [method, setMethod] = useState('bank_transfer');
    const [reference, setReference] = useState('');
    const [notes, setNotes] = useState('');
    const [ticked, setTicked] = useState<Set<string>>(new Set());
    const [typed, setTyped] = useState<Record<string, string>>({});
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        if (!open) {
            return;
        }

        setData(null);
        setErrors({});
        setAmount(
            payment
                ? toDollars(payment.unallocated_in_cents)
                : bankLine
                  ? toDollars(bankLine.amount_in_cents)
                  : '',
        );
        setPaidOn(bankLine?.posted_on ?? today());
        setMethod('bank_transfer');
        setReference('');
        setNotes(bankLine?.description ?? '');
        setTyped({});
        setTicked(new Set());

        http.cancel();
        void http
            .get(outstandingInvoices.url(customerId))
            .then((loaded) => {
                setData(loaded);

                const invoice = loaded.invoices.find(
                    (candidate) => candidate.id === invoiceId,
                );

                if (invoice && !payment && !bankLine) {
                    setTicked(new Set([invoice.id]));
                    setAmount(toDollars(invoice.balance_in_cents));
                }
            })
            .catch(() => undefined);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, customerId, invoiceId]);

    const invoices = useMemo(() => data?.invoices ?? [], [data]);
    const currency = data?.customer.currency ?? 'AUD';
    const money = (cents: number) => formatCents(cents, currency);
    const amountInCents = toCents(amount);
    const applied = spread(invoices, amountInCents, ticked, typed);
    const appliedTotal = Object.values(applied).reduce(
        (sum, cents) => sum + cents,
        0,
    );
    const leftOver = amountInCents - appliedTotal;
    const owingTotal = invoices.reduce(
        (sum, invoice) => sum + invoice.balance_in_cents,
        0,
    );

    const toggle = (id: string, checked: boolean) => {
        setTicked((current) => {
            const next = new Set(current);

            if (checked) {
                next.add(id);
            } else {
                next.delete(id);
            }

            return next;
        });

        if (!checked) {
            setTyped(({ [id]: _removed, ...rest }) => rest);
        }
    };

    const toggleAll = (checked: boolean) => {
        setTicked(
            checked
                ? new Set(invoices.map((invoice) => invoice.id))
                : new Set(),
        );
        setTyped({});

        if (checked && amountInCents === 0) {
            setAmount(toDollars(owingTotal));
        }
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        const allocations = invoices
            .filter((invoice) => (applied[invoice.id] ?? 0) > 0)
            .map((invoice) => ({
                invoice_id: invoice.id,
                amount: toDollars(applied[invoice.id]),
            }));

        router.post(
            payment
                ? StorePaymentAllocationsController.url(payment.id)
                : StorePaymentController.url(),
            payment
                ? { allocations }
                : {
                      customer_id: customerId,
                      amount,
                      paid_on: paidOn,
                      method,
                      reference: reference || null,
                      notes: notes || null,
                      allocations,
                      bank_transaction_id: bankLine?.id ?? null,
                  },
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (failed) => {
                    const byInvoice: Record<string, string> = {};

                    Object.entries(failed).forEach(([key, message]) => {
                        const match = key.match(/^allocations\.(\d+)\./);

                        if (match) {
                            const invoice = allocations[Number(match[1])];

                            if (invoice) {
                                byInvoice[`invoice.${invoice.invoice_id}`] =
                                    message;
                            }
                        }
                    });

                    setErrors({ ...failed, ...byInvoice });
                },
                onSuccess: () => {
                    onOpenChange(false);
                    onRecorded?.();
                },
            },
        );
    };

    const allTicked =
        invoices.length > 0 &&
        invoices.every((invoice) => ticked.has(invoice.id));

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[92vh] overflow-y-auto sm:max-w-3xl">
                <DialogTitle>
                    {payment
                        ? `Apply ${payment.reference_number}`
                        : bankLine
                          ? 'Record from bank feed'
                          : 'Record payment'}
                </DialogTitle>
                <DialogDescription>
                    {!data
                        ? 'Loading…'
                        : payment
                          ? `${money(payment.unallocated_in_cents)} of it isn't applied yet. Choose the invoices it pays.`
                          : bankLine
                            ? `${money(bankLine.amount_in_cents)} from ${data.customer.name} came into ${bankLine.bank_account_name ?? 'your account'}. Apply it to their invoices still owing; anything not applied is kept as their credit.`
                            : `Money ${data.customer.name} paid, applied to their invoices still owing. Anything not applied is kept as their credit.`}
                </DialogDescription>

                {data === null ? (
                    <div className="space-y-3">
                        <Skeleton className="h-9 w-full" />
                        <Skeleton className="h-9 w-full" />
                        <Skeleton className="h-32 w-full" />
                    </div>
                ) : (
                    <form onSubmit={submit} className="mt-2 space-y-6">
                        <InputError message={errors.allocations} />
                        <InputError
                            message={
                                errors.bank_transaction_id ?? errors.customer_id
                            }
                        />
                        <div
                            className={cn(
                                'grid gap-4 sm:grid-cols-2',
                                isApplying && 'hidden',
                            )}
                        >
                            <FormField
                                id="payment-amount"
                                label={`Amount (${currency})`}
                                error={errors.amount_in_cents}
                            >
                                <Input
                                    id="payment-amount"
                                    type="number"
                                    inputMode="decimal"
                                    step="0.01"
                                    min="0.01"
                                    value={amount}
                                    onChange={(event) =>
                                        setAmount(event.target.value)
                                    }
                                    readOnly={bankLine !== undefined}
                                    required
                                    autoFocus={!isApplying && !bankLine}
                                />
                            </FormField>
                            <FormField
                                id="payment-paid-on"
                                label="Paid on"
                                error={errors.paid_on}
                            >
                                <Input
                                    id="payment-paid-on"
                                    type="date"
                                    value={paidOn}
                                    onChange={(event) =>
                                        setPaidOn(event.target.value)
                                    }
                                    readOnly={bankLine !== undefined}
                                    required
                                />
                            </FormField>
                            <FormField
                                id="payment-method"
                                label="Method"
                                error={errors.method}
                            >
                                <NativeSelect
                                    id="payment-method"
                                    value={method}
                                    onChange={(event) =>
                                        setMethod(event.target.value)
                                    }
                                >
                                    {data.methods.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </FormField>
                            <FormField
                                id="payment-reference"
                                label="Reference"
                                hint="The bank or receipt reference, if there is one."
                                error={errors.reference}
                            >
                                <Input
                                    id="payment-reference"
                                    value={reference}
                                    onChange={(event) =>
                                        setReference(event.target.value)
                                    }
                                />
                            </FormField>
                            <div className="sm:col-span-2">
                                <FormField
                                    id="payment-notes"
                                    label="Notes"
                                    error={errors.notes}
                                >
                                    <Textarea
                                        id="payment-notes"
                                        rows={2}
                                        value={notes}
                                        onChange={(event) =>
                                            setNotes(event.target.value)
                                        }
                                    />
                                </FormField>
                            </div>
                        </div>

                        <section>
                            <h3 className="mb-2 text-sm font-medium">
                                Invoices still owing
                            </h3>
                            {invoices.length === 0 ? (
                                <p className="rounded-lg border border-dashed px-4 py-6 text-center text-sm text-muted-foreground">
                                    {data.customer.name} has no sent invoices
                                    still owing.
                                </p>
                            ) : (
                                <div className="overflow-x-auto rounded-lg border">
                                    <table className="w-full text-sm">
                                        <thead className="bg-muted/50 text-left text-xs text-muted-foreground">
                                            <tr>
                                                <th className="w-10 px-3 py-2">
                                                    <Checkbox
                                                        aria-label="Tick every invoice"
                                                        checked={allTicked}
                                                        onCheckedChange={(
                                                            checked,
                                                        ) =>
                                                            toggleAll(
                                                                checked ===
                                                                    true,
                                                            )
                                                        }
                                                    />
                                                </th>
                                                <th className="px-3 py-2 font-medium">
                                                    Invoice
                                                </th>
                                                <th className="px-3 py-2 font-medium">
                                                    Due
                                                </th>
                                                <th className="px-3 py-2 text-right font-medium">
                                                    Total
                                                </th>
                                                <th className="px-3 py-2 text-right font-medium">
                                                    Owing
                                                </th>
                                                <th className="w-36 px-3 py-2 text-right font-medium">
                                                    Payment
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y">
                                            {invoices.map((invoice) => {
                                                const isTicked = ticked.has(
                                                    invoice.id,
                                                );
                                                const error =
                                                    errors[
                                                        `invoice.${invoice.id}`
                                                    ];

                                                return (
                                                    <tr key={invoice.id}>
                                                        <td className="px-3 py-2 align-top">
                                                            <Checkbox
                                                                aria-label={`Pay ${invoice.reference}`}
                                                                checked={
                                                                    isTicked
                                                                }
                                                                onCheckedChange={(
                                                                    checked,
                                                                ) =>
                                                                    toggle(
                                                                        invoice.id,
                                                                        checked ===
                                                                            true,
                                                                    )
                                                                }
                                                            />
                                                        </td>
                                                        <td className="px-3 py-2 align-top font-mono">
                                                            {invoice.reference}
                                                            <InputError
                                                                message={error}
                                                            />
                                                        </td>
                                                        <td
                                                            className={cn(
                                                                'px-3 py-2 align-top whitespace-nowrap',
                                                                invoice.is_overdue &&
                                                                    'text-destructive',
                                                            )}
                                                        >
                                                            {invoice.due_on
                                                                ? dateFormatter.format(
                                                                      new Date(
                                                                          invoice.due_on,
                                                                      ),
                                                                  )
                                                                : '—'}
                                                        </td>
                                                        <td className="px-3 py-2 text-right align-top tabular-nums">
                                                            {money(
                                                                invoice.total_in_cents,
                                                            )}
                                                        </td>
                                                        <td className="px-3 py-2 text-right align-top tabular-nums">
                                                            {money(
                                                                invoice.balance_in_cents,
                                                            )}
                                                        </td>
                                                        <td className="px-3 py-1.5 align-top">
                                                            <Input
                                                                aria-label={`Payment for ${invoice.reference}`}
                                                                type="number"
                                                                inputMode="decimal"
                                                                step="0.01"
                                                                min="0"
                                                                className="h-8 text-right tabular-nums"
                                                                disabled={
                                                                    !isTicked
                                                                }
                                                                value={
                                                                    typed[
                                                                        invoice
                                                                            .id
                                                                    ] ??
                                                                    (isTicked
                                                                        ? toDollars(
                                                                              applied[
                                                                                  invoice
                                                                                      .id
                                                                              ] ??
                                                                                  0,
                                                                          )
                                                                        : '')
                                                                }
                                                                onChange={(
                                                                    event,
                                                                ) =>
                                                                    setTyped(
                                                                        (
                                                                            current,
                                                                        ) => ({
                                                                            ...current,
                                                                            [invoice.id]:
                                                                                event
                                                                                    .target
                                                                                    .value,
                                                                        }),
                                                                    )
                                                                }
                                                            />
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                        <tfoot className="border-t bg-muted/30 text-sm">
                                            <tr>
                                                <td
                                                    colSpan={4}
                                                    className="px-3 py-2 text-right text-muted-foreground"
                                                >
                                                    Owing in all
                                                </td>
                                                <td className="px-3 py-2 text-right tabular-nums">
                                                    {money(owingTotal)}
                                                </td>
                                                <td className="px-3 py-2 text-right font-medium tabular-nums">
                                                    {money(appliedTotal)}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            )}
                            {leftOver !== 0 && amountInCents > 0 && (
                                <p
                                    className={cn(
                                        'mt-2 text-sm',
                                        leftOver < 0
                                            ? 'text-destructive'
                                            : 'text-muted-foreground',
                                    )}
                                >
                                    {leftOver < 0
                                        ? `The invoices take ${money(-leftOver)} more than ${isApplying ? 'is left to apply' : 'the amount received'}.`
                                        : isApplying
                                          ? `${money(leftOver)} stays unapplied.`
                                          : `${money(leftOver)} will be kept as credit.`}
                                </p>
                            )}
                        </section>

                        <div className="flex justify-end gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => onOpenChange(false)}
                            >
                                Cancel
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                {isApplying ? 'Apply' : 'Record payment'}
                            </Button>
                        </div>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
}
