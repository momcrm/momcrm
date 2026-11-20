import { Form, Link, router, useHttp, usePage } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';
import UpdatePaymentController from '@/actions/App/Payment/Controllers/UpdatePaymentController';
import FormField from '@/components/form-field';
import NativeSelect from '@/components/native-select';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatCents } from '@/lib/money';
import { show as showCustomer } from '@/routes/customers';
import { show as showInvoice } from '@/routes/invoices';
import { destroy, details } from '@/routes/payments';
import type { PaymentDetails } from '@/types';

/** The query parameter that keeps a payment open over the page. */
const PAYMENT_PARAM = 'payment';

const dateFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
});

function formatDate(value: string | null): string {
    return value ? dateFormatter.format(new Date(value)) : '—';
}

/**
 * Which payment is open over the current page, kept in the URL (?payment=)
 * so it stays open on reload and when the link is shared.
 */
export function usePaymentDialog() {
    const { url } = usePage();
    const openPaymentId = new URL(url, 'http://localhost').searchParams.get(
        PAYMENT_PARAM,
    );

    const setOpenPayment = useCallback((id: string | null) => {
        const next = new URL(window.location.href);

        if (id === null) {
            next.searchParams.delete(PAYMENT_PARAM);
        } else {
            next.searchParams.set(PAYMENT_PARAM, id);
        }

        router.replace({
            url: `${next.pathname}${next.search}`,
            preserveScroll: true,
            preserveState: true,
        });
    }, []);

    const openPayment = useCallback(
        (id: string) => setOpenPayment(id),
        [setOpenPayment],
    );
    const closePayment = useCallback(
        () => setOpenPayment(null),
        [setOpenPayment],
    );

    return { openPaymentId, openPayment, closePayment };
}

function Detail({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex items-baseline justify-between gap-4 py-1.5">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="text-right font-medium">{value}</dd>
        </div>
    );
}

/**
 * Asks before deleting a payment. The server sends the page back without
 * the payment open over it.
 */
function DeletePayment({
    details: { payment, allocations },
}: {
    details: PaymentDetails;
}) {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    const remove = () =>
        router.delete(destroy.url(payment.id), {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onSuccess: () => setOpen(false),
        });

    return (
        <>
            <Button variant="outline" size="sm" onClick={() => setOpen(true)}>
                <Trash2 />
                Delete
            </Button>
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogTitle>
                        Delete {payment.reference_number}?
                    </DialogTitle>
                    <DialogDescription>
                        {allocations.length > 0
                            ? `It comes off ${allocations.map((allocation) => allocation.invoice_reference).join(', ')}, which will be owed again. This can't be undone.`
                            : "This can't be undone."}
                    </DialogDescription>
                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary" type="button">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            variant="destructive"
                            disabled={processing}
                            onClick={remove}
                        >
                            Delete payment
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

/**
 * Changes when and how a payment was made, its reference and notes.
 */
function EditPayment({
    details: { payment, methods },
    onDone,
}: {
    details: PaymentDetails;
    onDone: (saved: boolean) => void;
}) {
    return (
        <Form
            {...UpdatePaymentController.form(payment.id)}
            options={{ preserveScroll: true, preserveState: true }}
            onSuccess={() => onDone(true)}
            className="space-y-4"
        >
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <FormField
                            id="edit-paid-on"
                            label="Paid on"
                            error={errors.paid_on}
                        >
                            <Input
                                id="edit-paid-on"
                                name="paid_on"
                                type="date"
                                defaultValue={payment.paid_on ?? ''}
                                required
                            />
                        </FormField>
                        <FormField
                            id="edit-method"
                            label="Method"
                            error={errors.method}
                        >
                            <NativeSelect
                                id="edit-method"
                                name="method"
                                defaultValue={payment.method}
                            >
                                {methods.map((option) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </option>
                                ))}
                            </NativeSelect>
                        </FormField>
                        <div className="sm:col-span-2">
                            <FormField
                                id="edit-reference"
                                label="Reference"
                                error={errors.reference}
                            >
                                <Input
                                    id="edit-reference"
                                    name="reference"
                                    defaultValue={payment.reference ?? ''}
                                />
                            </FormField>
                        </div>
                        <div className="sm:col-span-2">
                            <FormField
                                id="edit-notes"
                                label="Notes"
                                error={errors.notes}
                            >
                                <Textarea
                                    id="edit-notes"
                                    name="notes"
                                    rows={2}
                                    defaultValue={payment.notes ?? ''}
                                />
                            </FormField>
                        </div>
                    </div>
                    <div className="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onDone(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            Save
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}

/**
 * The payment named in the URL, opened over the page it was picked from:
 * its details, the invoices it paid, and editing or deleting it. Put one on
 * any page that opens payments with usePaymentDialog().
 */
export default function PaymentDialog() {
    const { openPaymentId: paymentId, closePayment } = usePaymentDialog();
    const [loaded, setLoaded] = useState<PaymentDetails | null>(null);
    const [isEditing, setIsEditing] = useState(false);
    const http = useHttp<Record<string, never>, PaymentDetails>();

    const load = useCallback(
        (id: string) => {
            http.cancel();
            void http
                .get(details.url(id), {
                    // A link to a payment that's gone, or another team's.
                    onHttpException: () => {
                        toast.error("That payment couldn't be found.");
                        closePayment();

                        return false;
                    },
                })
                .then(setLoaded)
                .catch(() => undefined);
        },
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [],
    );

    useEffect(() => {
        setIsEditing(false);

        if (paymentId === null) {
            return;
        }

        setLoaded((current) =>
            current?.payment.id === paymentId ? current : null,
        );
        load(paymentId);
    }, [paymentId, load]);

    const shown =
        loaded && (paymentId === null || loaded.payment.id === paymentId)
            ? loaded
            : null;
    const payment = shown?.payment;
    const money = (cents: number) =>
        formatCents(cents, payment?.currency ?? 'AUD');

    return (
        <Dialog
            open={paymentId !== null}
            onOpenChange={(open) => !open && closePayment()}
        >
            <DialogContent className="max-h-[92vh] overflow-y-auto sm:max-w-xl">
                <div className="pr-8">
                    <DialogTitle className="font-mono text-sm">
                        {payment?.reference_number ?? 'Payment'}
                    </DialogTitle>
                    <DialogDescription className="truncate text-xs">
                        {payment
                            ? `${payment.customer_name ?? ''} · ${formatDate(payment.paid_on)}`
                            : 'Loading…'}
                    </DialogDescription>
                </div>

                {!shown || !payment ? (
                    <div className="space-y-3">
                        <Skeleton className="h-16 w-full" />
                        <Skeleton className="h-32 w-full" />
                    </div>
                ) : isEditing ? (
                    <EditPayment
                        details={shown}
                        onDone={(saved) => {
                            setIsEditing(false);

                            if (saved) {
                                load(payment.id);
                            }
                        }}
                    />
                ) : (
                    <div className="space-y-6">
                        <div className="rounded-lg bg-muted/60 p-4">
                            <div className="text-xs text-muted-foreground">
                                Received
                            </div>
                            <div className="text-2xl font-semibold tabular-nums">
                                {money(payment.amount_in_cents)}
                            </div>
                            <dl className="mt-3 divide-y border-t text-sm">
                                <Detail
                                    label="Customer"
                                    value={
                                        <Link
                                            href={showCustomer(
                                                payment.customer_id,
                                            )}
                                            className="underline-offset-4 hover:underline"
                                        >
                                            {payment.customer_name}
                                        </Link>
                                    }
                                />
                                <Detail
                                    label="Paid on"
                                    value={formatDate(payment.paid_on)}
                                />
                                <Detail
                                    label="Method"
                                    value={payment.method_label}
                                />
                                <Detail
                                    label="Reference"
                                    value={payment.reference ?? '—'}
                                />
                                {payment.unallocated_in_cents > 0 && (
                                    <Detail
                                        label="Not applied to an invoice"
                                        value={money(
                                            payment.unallocated_in_cents,
                                        )}
                                    />
                                )}
                            </dl>
                            {payment.notes && (
                                <p className="mt-3 border-t pt-3 text-sm whitespace-pre-line">
                                    {payment.notes}
                                </p>
                            )}
                        </div>

                        <section>
                            <h3 className="mb-2 text-sm font-medium">
                                Invoices paid
                            </h3>
                            {shown.allocations.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Not applied to any invoice.
                                </p>
                            ) : (
                                <ul className="divide-y rounded-lg border text-sm">
                                    {shown.allocations.map((allocation) => (
                                        <li
                                            key={allocation.id}
                                            className="flex items-center justify-between gap-4 px-3 py-2"
                                        >
                                            <Link
                                                href={showInvoice(
                                                    allocation.invoice_id,
                                                )}
                                                className="font-mono underline-offset-4 hover:underline"
                                            >
                                                {allocation.invoice_reference}
                                            </Link>
                                            <span className="tabular-nums">
                                                {money(
                                                    allocation.amount_in_cents,
                                                )}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>

                        <div className="flex flex-wrap justify-end gap-2">
                            {shown.can.delete && (
                                <DeletePayment details={shown} />
                            )}
                            {shown.can.update && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setIsEditing(true)}
                                >
                                    <Pencil />
                                    Edit
                                </Button>
                            )}
                        </div>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
