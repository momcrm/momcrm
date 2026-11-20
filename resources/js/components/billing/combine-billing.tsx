import { router } from '@inertiajs/react';
import { ExternalLink, Pencil, Plus, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import BillingDone from '@/components/billing/billing-done';
import { thisMonth } from '@/components/billing/billing-lines';
import Stepper from '@/components/billing/stepper';
import ChargeDialog from '@/components/charges/charge-dialog';
import InputError from '@/components/input-error';
import { useInvoiceDialog } from '@/components/invoices/invoice-dialog';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatCents } from '@/lib/money';
import { destroy } from '@/routes/charges';
import { bill } from '@/routes/customers';
import type { Charge, Product } from '@/types';

const STEPS = [
    { title: 'Verify', description: 'Verify invoice items' },
    { title: 'Completed', description: 'Billing completed' },
];

const dateFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
});

/**
 * Combine Invoice Items, as Amphora's: the lines waiting to be invoiced (from
 * Single Service Billing, Product Billing or a one-off charge), ticked,
 * each editable, combined into one invoice.
 */
export default function CombineBilling({
    customerId,
    currency,
    waiting,
    products,
    canInvoice,
    canCharge,
}: {
    customerId: string;
    currency: string;
    waiting: Charge[];
    products: Product[];
    canInvoice: boolean;
    canCharge: boolean;
}) {
    const [step, setStep] = useState(1);
    const [ticked, setTicked] = useState<Set<string>>(
        () => new Set(waiting.map((charge) => charge.id)),
    );
    const [editing, setEditing] = useState<Charge | 'new' | null>(null);
    const [invoiceId, setInvoiceId] = useState<string | null>(null);
    const [error, setError] = useState<string | undefined>();
    const [processing, setProcessing] = useState(false);
    const { openInvoice } = useInvoiceDialog();
    const waitingKey = waiting.map((charge) => charge.id).join(',');

    useEffect(() => {
        setTicked(new Set(waiting.map((charge) => charge.id)));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [waitingKey]);

    const picked = waiting.filter((charge) => ticked.has(charge.id));
    const total = picked.reduce(
        (sum, charge) => sum + charge.total_in_cents,
        0,
    );

    const create = () => {
        // Bill up to the latest of the lines picked, so a line dated next
        // month is still included.
        const month = picked
            .map((charge) => (charge.charged_on ?? '').slice(0, 7))
            .reduce(
                (latest, item) => (item > latest ? item : latest),
                thisMonth(),
            );

        router.post(
            bill.url(customerId),
            {
                month,
                line_keys: picked.map((charge) => `charge:${charge.id}`),
            },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (errors) => setError(Object.values(errors)[0]),
                onFlash: (flash) => {
                    const id = flash.invoiceId as string | undefined;

                    if (id) {
                        setInvoiceId(id);
                        setStep(2);
                    }
                },
            },
        );
    };

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
        <div>
            <Stepper steps={STEPS} current={step} />

            {step === 1 && (
                <div className="space-y-4">
                    {waiting.length === 0 ? (
                        <p className="rounded-lg border border-dashed px-4 py-6 text-center text-sm text-muted-foreground">
                            Nothing is waiting to be invoiced. Add lines with
                            Single Service Billing or Product Billing, or by
                            hand.
                        </p>
                    ) : (
                        <>
                            <ul className="divide-y rounded-lg border bg-card">
                                {waiting.map((charge) => (
                                    <li
                                        key={charge.id}
                                        className="flex items-center gap-3 px-3 py-2 text-sm"
                                    >
                                        <Checkbox
                                            id={`waiting-${charge.id}`}
                                            checked={ticked.has(charge.id)}
                                            onCheckedChange={(isOn) =>
                                                setTicked((current) => {
                                                    const next = new Set(
                                                        current,
                                                    );

                                                    if (isOn === true) {
                                                        next.add(charge.id);
                                                    } else {
                                                        next.delete(charge.id);
                                                    }

                                                    return next;
                                                })
                                            }
                                        />
                                        <Label
                                            htmlFor={`waiting-${charge.id}`}
                                            className="min-w-0 flex-1 font-normal"
                                        >
                                            <span className="block truncate">
                                                {charge.description}
                                                {charge.quantity > 1 &&
                                                    ` × ${charge.quantity}`}
                                            </span>
                                            <span className="text-xs text-muted-foreground">
                                                {charge.service_id
                                                    ? 'Service period'
                                                    : charge.product_id
                                                      ? 'Product'
                                                      : 'One-off charge'}
                                                {charge.charged_on &&
                                                    ` · ${dateFormatter.format(new Date(charge.charged_on))}`}
                                            </span>
                                        </Label>
                                        <span className="tabular-nums">
                                            {formatCents(
                                                charge.total_in_cents,
                                                charge.currency,
                                            )}
                                        </span>
                                        {canCharge && (
                                            <>
                                                <Button
                                                    variant="ghost"
                                                    size="icon-sm"
                                                    aria-label={`Edit ${charge.description}`}
                                                    onClick={() =>
                                                        setEditing(charge)
                                                    }
                                                >
                                                    <Pencil />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon-sm"
                                                    aria-label={`Delete ${charge.description}`}
                                                    onClick={() =>
                                                        remove(charge)
                                                    }
                                                >
                                                    <Trash2 />
                                                </Button>
                                            </>
                                        )}
                                    </li>
                                ))}
                            </ul>
                            <p className="text-sm text-muted-foreground">
                                {picked.length} of {waiting.length} lines ·{' '}
                                {formatCents(total, currency)} before tax
                            </p>
                        </>
                    )}
                    <InputError message={error} />
                    <div className="flex flex-wrap gap-2">
                        {canInvoice && waiting.length > 0 && (
                            <Button
                                disabled={processing || picked.length === 0}
                                onClick={create}
                            >
                                {processing && <Spinner />}
                                Create invoice
                            </Button>
                        )}
                        {canCharge && (
                            <Button
                                variant="outline"
                                onClick={() => setEditing('new')}
                            >
                                <Plus />
                                Add line
                            </Button>
                        )}
                    </div>
                </div>
            )}

            {step === 2 && invoiceId && (
                <BillingDone
                    title="The invoice was created."
                    actions={
                        <>
                            <Button onClick={() => openInvoice(invoiceId)}>
                                <ExternalLink />
                                Invoice details
                            </Button>
                            <Button
                                variant="outline"
                                onClick={() => {
                                    setInvoiceId(null);
                                    setStep(1);
                                }}
                            >
                                Back to waiting lines
                            </Button>
                        </>
                    }
                >
                    It is a draft. Open it to check it, then send it.
                </BillingDone>
            )}

            <ChargeDialog
                key={
                    editing === null
                        ? 'closed'
                        : editing === 'new'
                          ? 'new'
                          : editing.id
                }
                open={editing !== null}
                onOpenChange={(open) => !open && setEditing(null)}
                customerId={customerId}
                currency={currency}
                products={products}
                charge={editing && editing !== 'new' ? editing : undefined}
            />
        </div>
    );
}
