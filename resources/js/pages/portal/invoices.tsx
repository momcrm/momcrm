import { Head } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import SoftBadge from '@/components/soft-badge';
import { formatCents } from '@/lib/money';
import { formatDay } from '@/lib/dates';
import type { PortalInvoice } from '@/types';

/**
 * The customer's invoices: what each was for in total, what is still owing,
 * and its PDF.
 */
export default function PortalInvoices({
    invoices,
}: {
    invoices: PortalInvoice[];
}) {
    return (
        <>
            <Head title="Invoices" />

            <h1 className="text-xl font-semibold">Invoices</h1>

            {invoices.length === 0 ? (
                <EmptyState
                    icon={FileText}
                    title="No invoices yet"
                    description="Invoices show here once they're sent to you."
                />
            ) : (
                <div className="overflow-x-auto rounded-lg border bg-card">
                    <table className="w-full text-sm">
                        <thead className="border-b text-left text-xs text-muted-foreground">
                            <tr>
                                <th className="px-4 py-2.5 font-medium">
                                    Invoice
                                </th>
                                <th className="px-4 py-2.5 font-medium">
                                    Issued
                                </th>
                                <th className="px-4 py-2.5 font-medium">Due</th>
                                <th className="px-4 py-2.5 text-right font-medium">
                                    Total
                                </th>
                                <th className="px-4 py-2.5 text-right font-medium">
                                    Owing
                                </th>
                                <th className="px-4 py-2.5" />
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {invoices.map((invoice) => (
                                <tr key={invoice.id}>
                                    <td className="px-4 py-2.5 font-medium whitespace-nowrap">
                                        <a
                                            href={invoice.pdf_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="hover:underline"
                                        >
                                            {invoice.reference}
                                        </a>
                                    </td>
                                    <td className="px-4 py-2.5 whitespace-nowrap">
                                        {invoice.issued_on &&
                                            formatDay(invoice.issued_on)}
                                    </td>
                                    <td className="px-4 py-2.5 whitespace-nowrap">
                                        {invoice.due_on &&
                                            formatDay(invoice.due_on)}
                                    </td>
                                    <td className="px-4 py-2.5 text-right tabular-nums">
                                        {formatCents(
                                            invoice.total_in_cents,
                                            invoice.currency,
                                        )}
                                    </td>
                                    <td className="px-4 py-2.5 text-right tabular-nums">
                                        {invoice.balance_in_cents > 0
                                            ? formatCents(
                                                  invoice.balance_in_cents,
                                                  invoice.currency,
                                              )
                                            : '—'}
                                    </td>
                                    <td className="px-4 py-2.5 text-right">
                                        {invoice.status === 'paid' ? (
                                            <SoftBadge tone="success">
                                                Paid
                                            </SoftBadge>
                                        ) : invoice.is_overdue ? (
                                            <SoftBadge tone="destructive">
                                                Overdue
                                            </SoftBadge>
                                        ) : (
                                            <SoftBadge tone="neutral">
                                                To pay
                                            </SoftBadge>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </>
    );
}
