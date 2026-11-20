import { router } from '@inertiajs/react';
import { Send, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import type { TicketSelection } from '@/components/tickets/ticket-bulk';
import { sendMany } from '@/routes/invoices';

/**
 * The dark bar that floats at the foot of the invoices list once invoices
 * are picked: email them to their customers' contacts who get invoices.
 */
export function InvoiceBulkBar({ selection }: { selection: TicketSelection }) {
    const [isSending, setIsSending] = useState(false);
    const count = selection.ids.length;

    useEffect(() => {
        if (count === 0) {
            return;
        }

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                selection.clear();
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, [count, selection]);

    if (count === 0) {
        return null;
    }

    const sendAll = () =>
        router.post(
            sendMany.url(),
            { invoice_ids: selection.ids },
            {
                preserveScroll: true,
                onStart: () => setIsSending(true),
                onFinish: () => setIsSending(false),
                onSuccess: () => selection.clear(),
                onError: (errors) => {
                    const message = Object.values(errors)[0];

                    if (message) {
                        toast.error(message);
                    }
                },
            },
        );

    return (
        <div
            role="toolbar"
            aria-label="Bulk actions"
            className="fixed bottom-6 left-1/2 z-40 flex -translate-x-1/2 animate-in items-center gap-1 rounded-[10px] bg-foreground py-1.5 pr-1.5 pl-3.5 text-[12.5px] text-background shadow-pop duration-300 fade-in slide-in-from-bottom-2"
        >
            <span className="font-semibold whitespace-nowrap tabular-nums">
                {count} selected
            </span>
            <span className="mx-1 h-[18px] w-px bg-background/20" />
            <button
                type="button"
                disabled={isSending}
                title="Email each invoice to its customer's contacts who get invoices, else their primary contact. Drafts are marked sent."
                onClick={sendAll}
                className="flex h-[26px] items-center gap-1.5 rounded-md px-2 opacity-85 transition hover:bg-background/15 hover:opacity-100 disabled:opacity-50"
            >
                <Send className="size-3.5" />
                Send
            </button>
            <button
                type="button"
                aria-label="Clear selection"
                onClick={selection.clear}
                className="ml-0.5 flex size-[26px] items-center justify-center rounded-md opacity-85 transition hover:bg-background/15 hover:opacity-100"
            >
                <X className="size-3.5" />
            </button>
        </div>
    );
}
