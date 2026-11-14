import { Link } from '@inertiajs/react';
import InvoiceStatusBadge from '@/components/invoices/invoice-status-badge';
import { formatCents } from '@/lib/money';
import { show } from '@/routes/invoices';
import type { InvoiceListItem } from '@/types';

const dateFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
});

export default function InvoiceList({
    invoices,
    showCustomer = true,
}: {
    invoices: InvoiceListItem[];
    showCustomer?: boolean;
}) {
    return (
        <ul className="divide-y rounded-lg border">
            {invoices.map((invoice) => (
                <li key={invoice.id}>
                    <Link
                        href={show(invoice.id)}
                        className="flex items-center justify-between gap-4 px-4 py-3 hover:bg-accent"
                        prefetch
                    >
                        <div className="min-w-0">
                            <p className="truncate font-medium">
                                {invoice.reference}
                                {showCustomer && invoice.customer_name && (
                                    <span className="font-normal text-muted-foreground">
                                        {' '}
                                        · {invoice.customer_name}
                                    </span>
                                )}
                            </p>
                            <p className="truncate text-sm text-muted-foreground">
                                {invoice.due_on &&
                                    `Due ${dateFormatter.format(new Date(invoice.due_on))}`}
                            </p>
                        </div>
                        <div className="flex shrink-0 items-center gap-3">
                            <span className="font-medium tabular-nums">
                                {formatCents(invoice.total_in_cents)}
                            </span>
                            <InvoiceStatusBadge invoice={invoice} />
                        </div>
                    </Link>
                </li>
            ))}
        </ul>
    );
}
