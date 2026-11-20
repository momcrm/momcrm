import { Head } from '@inertiajs/react';
import { Receipt } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import { formatCents } from '@/lib/money';
import { formatDay } from '@/lib/dates';
import type { PortalReceipt } from '@/types';

/**
 * What the customer has paid, each with its receipt.
 */
export default function PortalReceipts({
    receipts,
}: {
    receipts: PortalReceipt[];
}) {
    return (
        <>
            <Head title="Receipts" />

            <h1 className="text-xl font-semibold">Receipts</h1>

            {receipts.length === 0 ? (
                <EmptyState
                    icon={Receipt}
                    title="No payments yet"
                    description="Receipts for your payments show here."
                />
            ) : (
                <ul className="divide-y overflow-hidden rounded-lg border bg-card">
                    {receipts.map((receipt) => (
                        <li key={receipt.id}>
                            <a
                                href={receipt.pdf_url}
                                target="_blank"
                                rel="noreferrer"
                                className="flex items-center gap-3 px-4 py-3 text-sm hover:bg-accent"
                            >
                                <span className="min-w-0 flex-1">
                                    <span className="block font-medium">
                                        {receipt.reference}
                                    </span>
                                    <span className="block text-xs text-muted-foreground">
                                        {receipt.paid_on &&
                                            formatDay(receipt.paid_on)}{' '}
                                        · {receipt.method}
                                    </span>
                                </span>
                                <span className="font-medium tabular-nums">
                                    {formatCents(
                                        receipt.amount_in_cents,
                                        receipt.currency,
                                    )}
                                </span>
                            </a>
                        </li>
                    ))}
                </ul>
            )}
        </>
    );
}
