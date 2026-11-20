import { Head } from '@inertiajs/react';
import { Download } from 'lucide-react';
import SendCustomerStatementController from '@/actions/App/Customer/Controllers/SendCustomerStatementController';
import { TabHeader } from '@/components/customers/workspace-tab';
import InvoiceDialog, {
    useInvoiceDialog,
} from '@/components/invoices/invoice-dialog';
import PaymentDialog, {
    usePaymentDialog,
} from '@/components/payments/payment-dialog';
import SendToContactsDialog from '@/components/send-to-contacts-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useWorkspaceBreadcrumbs } from '@/layouts/customer/layout';
import { formatCents } from '@/lib/money';
import { visitWithQuery } from '@/lib/query';
import { cn } from '@/lib/utils';
import { statement as statementTab } from '@/routes/customers';
import { pdf } from '@/routes/customers/statement';
import type {
    ContactRecipient,
    CustomerStatement as Statement,
    CustomerWorkspace,
    StatementEntry,
} from '@/types';

const RELOAD = ['statement'];

const dateFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
});

function formatDay(day: string): string {
    return dateFormatter.format(new Date(`${day}T00:00:00`));
}

export default function CustomerStatement({
    workspace,
    statement,
    contacts,
    canSend,
}: {
    workspace: CustomerWorkspace;
    statement: Statement;
    contacts: ContactRecipient[];
    canSend: boolean;
}) {
    const { customer } = workspace;
    useWorkspaceBreadcrumbs(customer, {
        title: 'Statement',
        href: statementTab(customer.id),
    });
    const { openInvoice } = useInvoiceDialog();
    const { openPayment } = usePaymentDialog();
    const money = (cents: number) => formatCents(cents, statement.currency);
    /** A balance below 0 is credit, shown as e.g. $20.00 CR. */
    const balance = (cents: number) =>
        cents < 0 ? `${money(-cents)} CR` : money(cents);
    const period = { from: statement.from, to: statement.to };
    const closing = statement.closing_balance_in_cents;

    const open = (entry: StatementEntry) =>
        entry.type === 'invoice'
            ? openInvoice(entry.id)
            : openPayment(entry.id);

    return (
        <>
            <Head title={`${customer.name} · Statement`} />
            <TabHeader
                title="Statement"
                description="Their account over a period: invoices charged, payments received and what they owe."
                action={
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <a
                                href={pdf.url(customer.id, {
                                    query: { ...period, download: 1 },
                                })}
                            >
                                <Download />
                                PDF
                            </a>
                        </Button>
                        {canSend && (
                            <SendToContactsDialog
                                url={SendCustomerStatementController.url(
                                    customer.id,
                                )}
                                data={period}
                                contacts={contacts}
                                title="Send statement"
                                description={`Email ${customer.name}'s statement from ${formatDay(statement.from)} to ${formatDay(statement.to)}, with the PDF attached.`}
                                trigger="Send statement"
                            />
                        )}
                    </div>
                }
            />

            <div className="flex flex-col gap-4">
                <div className="flex flex-wrap items-center gap-2">
                    <Input
                        aria-label="From"
                        type="date"
                        value={statement.from}
                        onChange={(event) =>
                            event.target.value &&
                            visitWithQuery(
                                { from: event.target.value },
                                { only: RELOAD },
                            )
                        }
                        className="h-8 w-auto text-[13px]"
                    />
                    <span className="text-xs text-muted-foreground">to</span>
                    <Input
                        aria-label="To"
                        type="date"
                        value={statement.to}
                        onChange={(event) =>
                            event.target.value &&
                            visitWithQuery(
                                { to: event.target.value },
                                { only: RELOAD },
                            )
                        }
                        className="h-8 w-auto text-[13px]"
                    />
                </div>

                <section className="grid overflow-hidden rounded-xl border bg-card shadow-xs sm:grid-cols-3">
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

                <div className="overflow-x-auto rounded-xl border bg-card shadow-xs">
                    <table className="w-full text-sm">
                        <thead className="border-b text-left text-xs text-muted-foreground">
                            <tr>
                                <th className="w-32 px-4 py-2.5 font-medium">
                                    Date
                                </th>
                                <th className="px-4 py-2.5 font-medium">
                                    Details
                                </th>
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
                                    {balance(
                                        statement.opening_balance_in_cents,
                                    )}
                                </td>
                            </tr>
                            {statement.entries.map((entry) => (
                                <tr
                                    key={`${entry.type}-${entry.id}`}
                                    className="cursor-pointer hover:bg-muted/50"
                                    onClick={() => open(entry)}
                                >
                                    <td className="px-4 py-2.5 whitespace-nowrap">
                                        {formatDay(entry.date)}
                                    </td>
                                    <td className="px-4 py-2.5">
                                        {entry.description}
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
                            ))}
                            {statement.entries.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        Nothing was charged or paid in this
                                        period.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <section aria-label="How old what they owe is">
                    <h3 className="mb-2 text-sm font-medium">
                        Owing today, by age
                    </h3>
                    <div className="grid overflow-hidden rounded-xl border bg-card shadow-xs sm:grid-cols-5">
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
            </div>

            <InvoiceDialog />
            <PaymentDialog />
        </>
    );
}
