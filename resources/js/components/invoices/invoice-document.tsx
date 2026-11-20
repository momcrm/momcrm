import { Link } from '@inertiajs/react';
import InvoiceStatusBadge from '@/components/invoices/invoice-status-badge';
import { formatCents } from '@/lib/money';
import { cn } from '@/lib/utils';
import { show as showCustomer } from '@/routes/customers';
import { index as paymentsIndex } from '@/routes/payments';
import type { InvoiceDocument as InvoiceDocumentData } from '@/types';

const dateFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
});

function formatDate(value: string | null): string {
    return value ? dateFormatter.format(new Date(value)) : '—';
}

function Eyebrow({
    className,
    children,
}: {
    className?: string;
    children: React.ReactNode;
}) {
    return (
        <div
            className={cn(
                'text-[10px] font-semibold tracking-[0.14em] text-subtle-foreground uppercase',
                className,
            )}
        >
            {children}
        </div>
    );
}

function MetaRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-baseline justify-between gap-4">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="font-medium tabular-nums">{value}</dd>
        </div>
    );
}

/**
 * An invoice laid out like the paper it prints as: who it's from and who it
 * bills, when it's due, its lines and totals, and how to pay. The PDF follows
 * the same layout.
 */
export default function InvoiceDocument({
    document: { title, invoice, items, totals, customer, from, payments },
    className,
}: {
    document: InvoiceDocumentData;
    className?: string;
}) {
    const money = (cents: number) => formatCents(cents, invoice.currency);
    const isPaid = invoice.status === 'paid';
    const balance = Math.max(0, totals.total_in_cents - invoice.paid_in_cents);
    const showsPaymentDetails =
        from.payment_details !== null &&
        (invoice.status === 'draft' || invoice.status === 'sent');

    return (
        <article
            className={cn(
                'overflow-hidden rounded-xl border bg-card shadow-xs',
                className,
            )}
        >
            <div className="h-1.5 bg-primary" />

            <div className="flex flex-col gap-8 p-6 sm:p-10">
                <header className="flex flex-wrap items-start justify-between gap-6">
                    <div className="min-w-0">
                        <Eyebrow>From</Eyebrow>
                        <div className="mt-1 text-xl font-semibold tracking-tight">
                            {from.name}
                        </div>
                        {from.tax_id && (
                            <div className="mt-1 text-sm font-medium">
                                {from.tax_id_label} {from.tax_id}
                            </div>
                        )}
                        <div className="mt-1 space-y-0.5 text-sm text-muted-foreground">
                            {from.billing_address && (
                                <div className="whitespace-pre-line">
                                    {from.billing_address}
                                </div>
                            )}
                            {from.billing_email && (
                                <div>{from.billing_email}</div>
                            )}
                            {from.billing_phone && (
                                <div>{from.billing_phone}</div>
                            )}
                        </div>
                    </div>
                    <div className="text-right">
                        <div className="text-2xl leading-none font-bold tracking-[0.12em] text-primary uppercase sm:text-3xl">
                            {title}
                        </div>
                        <div className="mt-2 font-mono text-sm text-muted-foreground">
                            {invoice.reference}
                        </div>
                        <div className="mt-2">
                            <InvoiceStatusBadge invoice={invoice} />
                        </div>
                    </div>
                </header>

                <div className="grid gap-6 border-t pt-8 sm:grid-cols-2">
                    <div className="min-w-0">
                        <Eyebrow>Bill to</Eyebrow>
                        <Link
                            href={showCustomer(customer.id)}
                            className="mt-1.5 block truncate text-base font-semibold underline-offset-4 hover:underline"
                        >
                            {customer.name}
                        </Link>
                        <div className="mt-1 space-y-0.5 text-sm text-muted-foreground">
                            {customer.abn && <div>ABN {customer.abn}</div>}
                            {customer.email && (
                                <div className="truncate">{customer.email}</div>
                            )}
                            {customer.phone && <div>{customer.phone}</div>}
                        </div>
                    </div>

                    <div className="rounded-lg bg-muted/60 p-4">
                        <dl className="space-y-1.5 text-sm">
                            <MetaRow
                                label="Issued"
                                value={formatDate(invoice.issued_on)}
                            />
                            <MetaRow
                                label="Due"
                                value={formatDate(invoice.due_on)}
                            />
                            {invoice.purchase_order && (
                                <MetaRow
                                    label="Purchase order"
                                    value={invoice.purchase_order}
                                />
                            )}
                            {invoice.paid_at && (
                                <MetaRow
                                    label="Paid"
                                    value={formatDate(invoice.paid_at)}
                                />
                            )}
                        </dl>
                        <div className="mt-3 flex items-end justify-between gap-4 border-t pt-3">
                            <Eyebrow className="pb-1">
                                {isPaid ? 'Amount paid' : 'Amount due'}
                            </Eyebrow>
                            <div
                                className={cn(
                                    'text-2xl font-semibold tracking-tight tabular-nums',
                                    invoice.is_overdue && 'text-destructive',
                                )}
                            >
                                {money(
                                    isPaid ? totals.total_in_cents : balance,
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b-[1.5px] border-foreground text-left">
                                {[
                                    'Description',
                                    'Qty',
                                    'Unit price',
                                    'Amount',
                                ].map((heading, index) => (
                                    <th
                                        key={heading}
                                        className={cn(
                                            'px-2 pb-2 text-[10px] font-semibold tracking-[0.14em] text-subtle-foreground uppercase first:pl-0 last:pr-0',
                                            index > 0 &&
                                                'w-28 text-right whitespace-nowrap',
                                        )}
                                    >
                                        {heading}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {items.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-2 py-8 text-center text-muted-foreground"
                                    >
                                        No lines on this invoice yet.
                                    </td>
                                </tr>
                            )}
                            {items.map((item) => (
                                <tr key={item.id}>
                                    <td className="py-3 pr-2">
                                        {item.description}
                                        {item.rental_starts_on &&
                                            item.rental_ends_on && (
                                                <div className="mt-0.5 text-xs text-muted-foreground">
                                                    {formatDate(
                                                        item.rental_starts_on,
                                                    )}{' '}
                                                    –{' '}
                                                    {formatDate(
                                                        item.rental_ends_on,
                                                    )}
                                                </div>
                                            )}
                                    </td>
                                    <td className="px-2 py-3 text-right tabular-nums">
                                        {item.quantity}
                                    </td>
                                    <td className="px-2 py-3 text-right whitespace-nowrap tabular-nums last:pr-0">
                                        {money(item.unit_price_in_cents)}
                                    </td>
                                    <td className="px-2 py-3 text-right whitespace-nowrap tabular-nums last:pr-0">
                                        {money(item.line_total_in_cents)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <dl className="ml-auto w-full max-w-xs space-y-2 text-sm">
                    <div className="flex justify-between gap-4">
                        <dt className="text-muted-foreground">Subtotal</dt>
                        <dd className="tabular-nums">
                            {money(totals.subtotal_in_cents)}
                        </dd>
                    </div>
                    <div className="flex justify-between gap-4">
                        <dt className="text-muted-foreground">
                            Tax ({invoice.tax_rate_basis_points / 100}%)
                        </dt>
                        <dd className="tabular-nums">
                            {money(totals.tax_in_cents)}
                        </dd>
                    </div>
                    <div className="flex items-baseline justify-between gap-4 border-t-[1.5px] border-foreground pt-3 text-base font-semibold">
                        <dt>Total {invoice.currency}</dt>
                        <dd className="text-primary tabular-nums">
                            {money(totals.total_in_cents)}
                        </dd>
                    </div>
                    {invoice.paid_in_cents > 0 && (
                        <>
                            <div className="flex justify-between gap-4">
                                <dt className="text-muted-foreground">Paid</dt>
                                <dd className="tabular-nums">
                                    −{money(invoice.paid_in_cents)}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-4 font-semibold">
                                <dt>Balance due</dt>
                                <dd className="tabular-nums">
                                    {money(balance)}
                                </dd>
                            </div>
                        </>
                    )}
                </dl>

                {payments.length > 0 && (
                    <section>
                        <Eyebrow>Payments</Eyebrow>
                        <ul className="mt-2 divide-y rounded-lg border text-sm">
                            {payments.map((payment) => (
                                <li
                                    key={payment.id}
                                    className="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 px-3 py-2"
                                >
                                    <Link
                                        href={paymentsIndex({
                                            query: {
                                                payment: payment.payment_id,
                                            },
                                        })}
                                        className="font-mono underline-offset-4 hover:underline"
                                    >
                                        {payment.payment_reference_number}
                                    </Link>
                                    <span className="text-muted-foreground">
                                        {formatDate(payment.paid_on)} ·{' '}
                                        {payment.method_label}
                                    </span>
                                    <span className="ml-auto tabular-nums">
                                        {money(payment.amount_in_cents)}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                {showsPaymentDetails && (
                    <section className="rounded-lg border border-l-[3px] border-l-primary px-4 py-3">
                        <Eyebrow>How to pay</Eyebrow>
                        <p className="mt-1.5 text-sm whitespace-pre-line">
                            {from.payment_details}
                        </p>
                    </section>
                )}

                {invoice.notes && (
                    <section>
                        <Eyebrow>Notes</Eyebrow>
                        <p className="mt-1.5 text-sm whitespace-pre-line text-muted-foreground">
                            {invoice.notes}
                        </p>
                    </section>
                )}
            </div>
        </article>
    );
}
