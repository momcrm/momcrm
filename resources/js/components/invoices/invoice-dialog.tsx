import { Link, router, useHttp, usePage } from '@inertiajs/react';
import { Link2, Maximize2 } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';
import InvoiceActions from '@/components/invoices/invoice-actions';
import InvoiceDocument from '@/components/invoices/invoice-document';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { details, show } from '@/routes/invoices';
import type { InvoiceDocument as InvoiceDocumentData } from '@/types';

/** The query parameter that keeps an invoice open over the page. */
const INVOICE_PARAM = 'invoice';

/**
 * Which invoice is open over the current page, kept in the URL (?invoice=)
 * so it stays open on reload, after a status change and when the link is
 * shared. Opening and closing change the URL without asking the server.
 */
export function useInvoiceDialog() {
    const { url } = usePage();
    const openInvoiceId = new URL(url, 'http://localhost').searchParams.get(
        INVOICE_PARAM,
    );

    const setOpenInvoice = useCallback((id: string | null) => {
        const next = new URL(window.location.href);

        if (id === null) {
            next.searchParams.delete(INVOICE_PARAM);
        } else {
            next.searchParams.set(INVOICE_PARAM, id);
        }

        router.replace({
            url: `${next.pathname}${next.search}`,
            preserveScroll: true,
            preserveState: true,
        });
    }, []);

    const openInvoice = useCallback(
        (id: string) => setOpenInvoice(id),
        [setOpenInvoice],
    );
    const closeInvoice = useCallback(
        () => setOpenInvoice(null),
        [setOpenInvoice],
    );

    return { openInvoiceId, openInvoice, closeInvoice };
}

/**
 * The invoice named in the URL, opened over the page it was picked from and
 * fetched when it opens. Its status can change in place; the page behind it
 * reloads with it. Put one on any page that opens invoices with
 * useInvoiceDialog().
 */
export default function InvoiceDialog() {
    const { openInvoiceId: invoiceId, closeInvoice } = useInvoiceDialog();
    const [document, setDocument] = useState<InvoiceDocumentData | null>(null);
    const http = useHttp<Record<string, never>, InvoiceDocumentData>();

    const load = useCallback(
        (id: string) => {
            http.cancel();
            void http
                .get(details.url(id), {
                    // A link to an invoice that's gone, or another team's.
                    onHttpException: () => {
                        toast.error("That invoice couldn't be found.");
                        closeInvoice();

                        return false;
                    },
                })
                .then(setDocument)
                .catch(() => undefined);
        },
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [],
    );

    useEffect(() => {
        if (invoiceId === null) {
            return;
        }

        setDocument((current) =>
            current?.invoice.id === invoiceId ? current : null,
        );
        load(invoiceId);
    }, [invoiceId, load]);

    const copyLink = () => {
        void navigator.clipboard
            .writeText(window.location.href)
            .then(() => toast.success('Link copied.'));
    };

    const shown =
        document && (invoiceId === null || document.invoice.id === invoiceId)
            ? document
            : null;

    return (
        <Dialog
            open={invoiceId !== null}
            onOpenChange={(open) => !open && closeInvoice()}
        >
            <DialogContent className="flex max-h-[92vh] flex-col gap-0 overflow-hidden p-0 pt-0 sm:max-w-3xl [&>[data-slot=dialog-close-button]]:top-[17.5px]">
                <div className="flex shrink-0 flex-wrap items-center gap-3 border-b px-4 py-3 pr-12">
                    <div className="min-w-0 flex-1">
                        <DialogTitle className="font-mono text-sm">
                            {shown?.invoice.reference ?? 'Invoice'}
                        </DialogTitle>
                        <DialogDescription className="truncate text-xs">
                            {shown ? shown.customer.name : 'Loading…'}
                        </DialogDescription>
                    </div>
                    {shown && (
                        <>
                            <InvoiceActions
                                invoice={shown.invoice}
                                onChanged={() => load(shown.invoice.id)}
                            />
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Copy link"
                                        onClick={copyLink}
                                    >
                                        <Link2 />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>Copy link</TooltipContent>
                            </Tooltip>
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="icon" asChild>
                                        <Link
                                            href={show(shown.invoice.id)}
                                            aria-label="Open as a page"
                                        >
                                            <Maximize2 />
                                        </Link>
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>Open as a page</TooltipContent>
                            </Tooltip>
                        </>
                    )}
                </div>

                <div className="min-h-0 flex-1 overflow-y-auto bg-muted/50 p-3 sm:p-6">
                    {shown ? (
                        <InvoiceDocument document={shown} />
                    ) : (
                        <InvoiceDocumentSkeleton />
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}

function InvoiceDocumentSkeleton() {
    return (
        <div className="space-y-8 rounded-xl border bg-card p-6 sm:p-10">
            <div className="flex justify-between">
                <div className="space-y-2">
                    <Skeleton className="h-3 w-12" />
                    <Skeleton className="h-6 w-40" />
                </div>
                <div className="flex flex-col items-end gap-2">
                    <Skeleton className="h-8 w-36" />
                    <Skeleton className="h-4 w-20" />
                </div>
            </div>
            <div className="grid gap-6 sm:grid-cols-2">
                <div className="space-y-2">
                    <Skeleton className="h-3 w-12" />
                    <Skeleton className="h-5 w-48" />
                    <Skeleton className="h-4 w-32" />
                </div>
                <Skeleton className="h-32" />
            </div>
            <div className="space-y-3">
                <Skeleton className="h-4 w-full" />
                <Skeleton className="h-4 w-full" />
                <Skeleton className="h-4 w-2/3" />
            </div>
        </div>
    );
}
