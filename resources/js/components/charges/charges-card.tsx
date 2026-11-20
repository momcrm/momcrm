import { router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import ChargeDialog from '@/components/charges/charge-dialog';
import { useInvoiceDialog } from '@/components/invoices/invoice-dialog';
import { Button } from '@/components/ui/button';
import { formatCents } from '@/lib/money';
import { destroy } from '@/routes/charges';
import type { Charge, Product } from '@/types';

const dateFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
});

/**
 * A customer's one-off charges: those waiting for their next invoice, which
 * can be changed or removed, then the recently billed ones with the invoice
 * each is on.
 */
export default function ChargesCard({
    customerId,
    currency,
    charges,
    products,
    canCharge,
}: {
    customerId: string;
    currency: string;
    charges: Charge[];
    products: Product[];
    canCharge: boolean;
}) {
    const [editing, setEditing] = useState<{ charge: Charge } | 'new' | null>(
        null,
    );
    const { openInvoice } = useInvoiceDialog();
    const waiting = charges.filter((charge) => charge.invoice === null);
    const waitingTotal = waiting.reduce(
        (sum, charge) => sum + charge.total_in_cents,
        0,
    );

    const remove = (charge: Charge) =>
        router.delete(destroy.url(charge.id), {
            preserveScroll: true,
            onError: (errors) => {
                const message = Object.values(errors)[0];

                if (message) {
                    toast.error(message);
                }
            },
        });

    return (
        <section className="mb-6 rounded-lg border bg-card shadow-xs">
            <div className="flex items-center justify-between gap-4 border-b px-4 py-3">
                <div>
                    <h2 className="text-sm font-medium">One-off charges</h2>
                    <p className="text-xs text-muted-foreground">
                        {waiting.length === 0
                            ? 'Nothing waiting for their next invoice.'
                            : `${formatCents(waitingTotal, currency)} waiting for their next invoice.`}
                    </p>
                </div>
                {canCharge && (
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={() => setEditing('new')}
                    >
                        <Plus />
                        Add charge
                    </Button>
                )}
            </div>
            {charges.length > 0 && (
                <ul className="divide-y">
                    {charges.map((charge) => (
                        <li
                            key={charge.id}
                            className="flex items-center gap-3 px-4 py-2 text-sm"
                        >
                            <div className="min-w-0 flex-1">
                                <p className="truncate font-medium">
                                    {charge.description}
                                    {charge.quantity > 1 &&
                                        ` × ${charge.quantity}`}
                                </p>
                                <p className="truncate text-xs text-muted-foreground">
                                    {charge.charged_on &&
                                        dateFormatter.format(
                                            new Date(charge.charged_on),
                                        )}
                                    {' · '}
                                    {charge.invoice ? (
                                        <button
                                            type="button"
                                            className="underline-offset-4 hover:underline"
                                            onClick={() =>
                                                openInvoice(charge.invoice!.id)
                                            }
                                        >
                                            on {charge.invoice.reference}
                                        </button>
                                    ) : (
                                        'waiting'
                                    )}
                                </p>
                            </div>
                            <span className="font-medium tabular-nums">
                                {formatCents(
                                    charge.total_in_cents,
                                    charge.currency,
                                )}
                            </span>
                            {charge.invoice === null && (
                                <>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        aria-label={`Edit ${charge.description}`}
                                        onClick={() => setEditing({ charge })}
                                    >
                                        <Pencil />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        aria-label={`Delete ${charge.description}`}
                                        onClick={() => remove(charge)}
                                    >
                                        <Trash2 />
                                    </Button>
                                </>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            <ChargeDialog
                key={
                    editing === null
                        ? 'closed'
                        : editing === 'new'
                          ? 'new'
                          : editing.charge.id
                }
                open={editing !== null}
                onOpenChange={(open) => !open && setEditing(null)}
                customerId={customerId}
                currency={currency}
                products={products}
                charge={
                    editing && editing !== 'new' ? editing.charge : undefined
                }
            />
        </section>
    );
}
