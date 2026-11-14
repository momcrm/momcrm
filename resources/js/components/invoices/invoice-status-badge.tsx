import { Badge } from '@/components/ui/badge';
import type { Invoice } from '@/types';

const labels: Record<Invoice['status'], string> = {
    draft: 'Draft',
    sent: 'Sent',
    paid: 'Paid',
    void: 'Void',
};

export default function InvoiceStatusBadge({
    invoice,
}: {
    invoice: Pick<Invoice, 'status' | 'is_overdue'>;
}) {
    if (invoice.is_overdue) {
        return <Badge variant="destructive">Overdue</Badge>;
    }

    return (
        <Badge
            variant={
                invoice.status === 'paid'
                    ? 'default'
                    : invoice.status === 'void'
                      ? 'outline'
                      : 'secondary'
            }
        >
            {labels[invoice.status]}
        </Badge>
    );
}
