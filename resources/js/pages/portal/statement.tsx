import { Head, router } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDay } from '@/lib/dates';
import { formatCents } from '@/lib/money';
import { cn } from '@/lib/utils';
import { pdf as invoicePdf } from '@/routes/portal/invoices';
import { pdf as receiptPdf } from '@/routes/portal/receipts';
import { statement as statementPage } from '@/routes/portal';
import { pdf } from '@/routes/portal/statement';
import type { CustomerStatement, StatementEntry } from '@/types';

/**
 * The customer's account over a period: opening and closing balance, each
 * charge and payment, and how old what they owe is.
 */
export default function PortalStatement({
    statement,
    canSeeInvoices,
    canSeeReceipts,
}: {
    statement: CustomerStatement;
    canSeeInvoices: boolean;
    canSeeReceipts: boolean;
}) {
    const money = (cents: number) => formatCents(cents, statement.currency);
    /** A balance below 0 is credit, shown as e.g. $20.00 CR. */
    const balance = (cents: number) =>
        cents < 0 ? `${money(-cents)} CR` : money(cents);
    const period = { from: statement.from, to: statement.to };
    const closing = statement.closing_balance_in_cents;
    const change = (key: 'from' | 'to', value: string) =>
        value &&
        router.get(
            statementPage.url({ query: { ...period, [key]: value } }),
            {},
            { preserveScroll: true },
        );
    const linkFor = (entry: StatementEntry): string | null => {
        if (entry.type === 'invoice') {
            return canSeeInvoices ? invoicePdf.url(entry.id) : null;
        }

        return canSeeReceipts ? receiptPdf.url(entry.id) : null;
    };

    return (
        <>
            <Head title="Statement" />

            <div className="flex flex-wrap items-center justify-between gap-3">
                <h1 className="text-xl font-semibold">Statement</h1>
                <div className="flex flex-wrap items-center gap-2">
                    <Input
                        aria-label="From"
                        type="date"
                        value={statement.from}
                        onChange={(event) => change('from', event.target.value)}
                        className="h-8 w-auto text-[13px]"
                    />
                    <span className="text-xs text-muted-foreground">to</span>
                    <Input
                        aria-label="To"
                        type="date"
                        value={statement.to}
                        onChange={(event) => change('to', event.target.value)}
                        className="h-8 w-auto text-[13px]"
                    />
                    <Button variant="outline" size="sm" asChild>
                        <a
                            href={pdf.url({
                                query: { ...period, download: 1 },
                            })}
                        >
                            <Download />
                            PDF
                        </a>
                    </Button>
                </div>
            </div>

            <section className="grid overflow-hidden rounded-lg border bg-card sm:grid-cols-3">
                {[
                    {
                        label: 'Opening balance',
                        value: balance(statement.opening_balance_in_cents),
                    },
                    {
                        label: closing < 0 ? 'In credit' : 'Balance owing',
                        value: money(Math.abs(closing)),
                    },
                    {
                        label: 'Credit available',
                        value: money(statement.credit_in_cents),
                    },
                ].map((figure) => (
                    <div
                        key={figure.label}
                        className="flex flex-col gap-1 border-b p-4 last:border-b-0 sm:border-r sm:border-b-0 sm:last:border-r-0"
                    >
                        <span className="text-xs text-muted-foreground">
                            {figure.label}
                        </span>
                        <span className="text-2xl font-semibold tabular-nums">
                            {figure.value}
                        </span>
                    </div>
                ))}
            </section>

            <div className="overflow-x-auto rounded-lg border bg-card">
                <table className="w-full text-sm">
                    <thead className="border-b text-left text-xs text-muted-foreground">
                        <tr>
                            <th className="w-32 px-4 py-2.5 font-medium">
                                Date
                            </th>
                            <th className="px-4 py-2.5 font-medium">Details</th>
                            <th className="w-32 px-4 py-2.5 text-right font-medium">
                                Charged
                            </th>
                            <th className="w-32 px-4 py-2.5 text-right font-medium">
                                Paid
                            </th>
                            <th className="w-36 px-4 py-2.5 text-right font-medium">
                                Balance
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        <tr className="text-muted-foreground">
                            <td className="px-4 py-2.5">
                                {formatDay(statement.from)}
                            </td>
                            <td className="px-4 py-2.5">Opening balance</td>
                            <td />
                            <td />
                            <td className="px-4 py-2.5 text-right tabular-nums">
                                {balance(statement.opening_balance_in_cents)}
                            </td>
                        </tr>
                        {statement.entries.map((entry) => {
                            const href = linkFor(entry);

                            return (
                                <tr key={`${entry.type}-${entry.id}`}>
                                    <td className="px-4 py-2.5 whitespace-nowrap">
                                        {formatDay(entry.date)}
                                    </td>
                                    <td className="px-4 py-2.5">
                                        {href ? (
                                            <a
                                                href={href}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="hover:underline"
                                            >
                                                {entry.description}
                                            </a>
                                        ) : (
                                            entry.description
                                        )}
                                    </td>
                                    <td className="px-4 py-2.5 text-right tabular-nums">
                                        {entry.debit_in_cents > 0 &&
                                            money(entry.debit_in_cents)}
                                    </td>
                                    <td className="px-4 py-2.5 text-right tabular-nums">
                                        {entry.credit_in_cents > 0 &&
                                            money(entry.credit_in_cents)}
                                    </td>
                                    <td className="px-4 py-2.5 text-right font-medium tabular-nums">
                                        {balance(entry.balance_in_cents)}
                                    </td>
                                </tr>
                            );
                        })}
                        {statement.entries.length === 0 && (
                            <tr>
                                <td
                                    colSpan={5}
                                    className="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Nothing was charged or paid in this period.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            <section aria-label="How old what you owe is">
                <h2 className="mb-2 text-sm font-medium">
                    Owing today, by age
                </h2>
                <div className="grid overflow-hidden rounded-lg border bg-card sm:grid-cols-5">
                    {statement.ages.map((age) => (
                        <div
                            key={age.key}
                            className="flex flex-col gap-1 border-b p-3 last:border-b-0 sm:border-r sm:border-b-0 sm:last:border-r-0"
                        >
                            <span
                                className={cn(
                                    'text-xs text-muted-foreground',
                                    age.key !== 'current' &&
                                        age.amount_in_cents > 0 &&
                                        'text-destructive',
                                )}
                            >
                                {age.label}
                            </span>
                            <span className="font-semibold tabular-nums">
                                {money(age.amount_in_cents)}
                            </span>
                        </div>
                    ))}
                </div>
            </section>
        </>
    );
}
