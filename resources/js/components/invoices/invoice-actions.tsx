import { Link, router } from '@inertiajs/react';
import { Download, Pencil } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import MarkInvoicePaidController from '@/actions/App/Invoice/Controllers/MarkInvoicePaidController';
import MarkInvoiceSentController from '@/actions/App/Invoice/Controllers/MarkInvoiceSentController';
import VoidInvoiceController from '@/actions/App/Invoice/Controllers/VoidInvoiceController';
import { Button } from '@/components/ui/button';
import { edit, pdf } from '@/routes/invoices';
import type { Invoice } from '@/types';

/**
 * What can be done with an invoice from its page or its dialog: download it
 * as a PDF, edit a draft, and move it along from draft to sent to paid. A
 * status change stays on the page it was made from, then calls onChanged.
 */
export default function InvoiceActions({
    invoice,
    onChanged,
}: {
    invoice: Pick<Invoice, 'id' | 'status'>;
    onChanged?: () => void;
}) {
    const [processing, setProcessing] = useState(false);

    const changeStatus = (url: string) => {
        router.post(
            url,
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onSuccess: () => onChanged?.(),
                onError: (errors) => {
                    const message = Object.values(errors)[0];

                    if (message) {
                        toast.error(message);
                    }
                },
            },
        );
    };

    const isOpen = invoice.status === 'draft' || invoice.status === 'sent';

    return (
        <div className="flex flex-wrap items-center gap-2">
            <Button variant="outline" asChild>
                <a href={pdf.url(invoice.id, { query: { download: 1 } })}>
                    <Download />
                    PDF
                </a>
            </Button>
            {invoice.status === 'draft' && (
                <Button variant="outline" asChild>
                    <Link href={edit(invoice.id)}>
                        <Pencil />
                        Edit
                    </Link>
                </Button>
            )}
            {isOpen && (
                <Button
                    variant="outline"
                    disabled={processing}
                    onClick={() =>
                        changeStatus(VoidInvoiceController.url(invoice.id))
                    }
                >
                    Void
                </Button>
            )}
            {invoice.status === 'draft' && (
                <Button
                    disabled={processing}
                    onClick={() =>
                        changeStatus(MarkInvoiceSentController.url(invoice.id))
                    }
                >
                    Mark as sent
                </Button>
            )}
            {invoice.status === 'sent' && (
                <Button
                    disabled={processing}
                    onClick={() =>
                        changeStatus(MarkInvoicePaidController.url(invoice.id))
                    }
                >
                    Mark as paid
                </Button>
            )}
        </div>
    );
}
