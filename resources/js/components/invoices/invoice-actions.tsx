import { Link, router } from '@inertiajs/react';
import { Banknote, Download, Pencil } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import ApplyInvoiceCreditController from '@/actions/App/Payment/Controllers/ApplyInvoiceCreditController';
import MarkInvoiceSentController from '@/actions/App/Invoice/Controllers/MarkInvoiceSentController';
import VoidInvoiceController from '@/actions/App/Invoice/Controllers/VoidInvoiceController';
import RecordPaymentDialog from '@/components/payments/record-payment-dialog';
import { Button } from '@/components/ui/button';
import { edit, pdf } from '@/routes/invoices';
import type { Invoice } from '@/types';

/**
 * What can be done with an invoice from its page or its dialog: download it
 * as a PDF, edit a draft, mark it sent, void it, and record a payment for
 * it, which makes it paid once nothing is owing. A change stays on the page
 * it was made from, then calls onChanged.
 */
export default function InvoiceActions({
    invoice,
    creditInCents = 0,
    onChanged,
}: {
    invoice: Pick<Invoice, 'id' | 'status' | 'customer_id' | 'paid_in_cents'>;
    /** The customer's credit, offered to pay a sent invoice. */
    creditInCents?: number;
    onChanged?: () => void;
}) {
    const [processing, setProcessing] = useState(false);
    const [isPaying, setIsPaying] = useState(false);

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

    const canVoid = invoice.status === 'draft' || invoice.status === 'sent';

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
            {canVoid && (
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
                <>
                    {creditInCents > 0 && (
                        <Button
                            variant="outline"
                            disabled={processing}
                            onClick={() =>
                                changeStatus(
                                    ApplyInvoiceCreditController.url(
                                        invoice.id,
                                    ),
                                )
                            }
                        >
                            Apply credit
                        </Button>
                    )}
                    <Button onClick={() => setIsPaying(true)}>
                        <Banknote />
                        Record payment
                    </Button>
                    <RecordPaymentDialog
                        customerId={invoice.customer_id}
                        invoiceId={invoice.id}
                        open={isPaying}
                        onOpenChange={setIsPaying}
                        onRecorded={onChanged}
                    />
                </>
            )}
        </div>
    );
}
