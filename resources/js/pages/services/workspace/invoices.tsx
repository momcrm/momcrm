import { Head, Link } from '@inertiajs/react';
import { TabHeader } from '@/components/customers/workspace-tab';
import EmptyState from '@/components/empty-state';
import { formatDay } from '@/components/services/service-detail';
import { Badge } from '@/components/ui/badge';
import { useServiceBreadcrumbs } from '@/layouts/service/layout';
import { formatCents } from '@/lib/money';
import { show as showInvoice } from '@/routes/invoices';
import { invoices } from '@/routes/services';
import type { ServiceInvoiceLine, ServiceWorkspace } from '@/types';

export default function ServiceInvoices({
    workspace,
    lines,
}: {
    workspace: ServiceWorkspace;
    lines: ServiceInvoiceLine[];
}) {
    const { service } = workspace;
    useServiceBreadcrumbs(workspace, {
        title: 'Invoices',
        href: invoices(service.id),
    });

    return (
        <>
            <Head title={`${service.name} · Invoices`} />
            <TabHeader
                title="Invoices"
                description="Every invoice line that billed this service, newest first."
            />
            {lines.length > 0 ? (
                <ul className="divide-y rounded-xl border bg-card shadow-xs">
                    {lines.map((line) => (
                        <li key={line.id}>
                            <Link
                                href={showInvoice(line.invoice.id)}
                                className="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-accent/50"
                            >
                                <span className="w-24 shrink-0 font-mono text-xs text-muted-foreground">
                                    {line.invoice.reference}
                                </span>
                                <span className="min-w-0 flex-1 truncate">
                                    {line.description}
                                    <span className="ml-2 text-xs text-muted-foreground">
                                        {line.quantity} ×{' '}
                                        {formatCents(line.unit_price_in_cents)}
                                    </span>
                                </span>
                                <span className="hidden text-xs text-muted-foreground sm:inline">
                                    {formatDay(line.invoice.issued_on)}
                                </span>
                                <Badge variant="outline" className="capitalize">
                                    {line.invoice.status}
                                </Badge>
                                <span className="w-24 text-right tabular-nums">
                                    {formatCents(line.line_total_in_cents)}
                                </span>
                            </Link>
                        </li>
                    ))}
                </ul>
            ) : (
                <EmptyState
                    title="Not invoiced yet"
                    description="Invoice lines made from this service show here."
                />
            )}
        </>
    );
}
