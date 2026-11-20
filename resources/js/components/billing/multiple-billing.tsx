import { router, useHttp } from '@inertiajs/react';
import { ExternalLink } from 'lucide-react';
import { useState } from 'react';
import BillingDone from '@/components/billing/billing-done';
import BillingLines from '@/components/billing/billing-lines';
import Stepper from '@/components/billing/stepper';
import InputError from '@/components/input-error';
import { useInvoiceDialog } from '@/components/invoices/invoice-dialog';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { formatCents } from '@/lib/money';
import { bill } from '@/routes/customers';
import { preview } from '@/routes/customers/billing';
import type { CustomerBilling, Service } from '@/types';

const STEPS = [
    { title: 'Parameters', description: 'Select services and month' },
    { title: 'Verify', description: 'Verify invoice items' },
    { title: 'Completed', description: 'Billing completed' },
];

const dateFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
});

/**
 * Multiple Service Billing, as Amphora's: pick services and a month, verify
 * what they are due (with any waiting lines), and draft one invoice.
 */
export default function MultipleBilling({
    customerId,
    currency,
    services,
    defaultMonth,
    canInvoice,
}: {
    customerId: string;
    currency: string;
    services: Service[];
    defaultMonth: string;
    canInvoice: boolean;
}) {
    const [step, setStep] = useState(1);
    const [month, setMonth] = useState(defaultMonth);
    const [picked, setPicked] = useState<Set<string>>(
        () => new Set(services.map((service) => service.id)),
    );
    const [data, setData] = useState<CustomerBilling | null>(null);
    const [ticked, setTicked] = useState<Set<string>>(new Set());
    const [invoiceId, setInvoiceId] = useState<string | null>(null);
    const [error, setError] = useState<string | undefined>();
    const [processing, setProcessing] = useState(false);
    const http = useHttp<Record<string, never>, CustomerBilling>();
    const { openInvoice } = useInvoiceDialog();

    const verify = () => {
        setData(null);
        setError(undefined);
        setStep(2);
        void http
            .get(
                preview.url(customerId, {
                    query: {
                        month,
                        // None picked still has to say so, or it means all.
                        services: picked.size > 0 ? [...picked] : ['none'],
                    },
                }),
            )
            .then((loaded) => {
                setData(loaded);
                setTicked(new Set(loaded.lines.map((line) => line.key)));
            })
            .catch(() => undefined);
    };

    const create = () =>
        router.post(
            bill.url(customerId),
            { month, line_keys: [...ticked] },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (errors) => setError(Object.values(errors)[0]),
                onFlash: (flash) => {
                    const id = flash.invoiceId as string | undefined;

                    if (id) {
                        setInvoiceId(id);
                        setStep(3);
                    }
                },
            },
        );

    const restart = () => {
        setStep(1);
        setData(null);
        setInvoiceId(null);
        setError(undefined);
    };

    const toggle = (set: Set<string>, key: string, isOn: boolean) => {
        const next = new Set(set);

        if (isOn) {
            next.add(key);
        } else {
            next.delete(key);
        }

        return next;
    };

    const lines = data?.lines ?? [];
    const total = lines
        .filter((line) => ticked.has(line.key))
        .reduce((sum, line) => sum + line.total_in_cents, 0);

    return (
        <div>
            <Stepper steps={STEPS} current={step} />

            {step === 1 && (
                <div className="space-y-5">
                    <div className="grid max-w-48 gap-2">
                        <Label htmlFor="multiple-month">Month</Label>
                        <Input
                            id="multiple-month"
                            type="month"
                            value={month}
                            onChange={(event) =>
                                event.target.value &&
                                setMonth(event.target.value)
                            }
                        />
                    </div>
                    <fieldset className="space-y-2">
                        <legend className="text-sm font-medium">
                            Services
                        </legend>
                        {services.length === 0 ? (
                            <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                                They have no active services on a rate. Waiting
                                lines can still be invoiced.
                            </p>
                        ) : (
                            <ul className="divide-y rounded-lg border bg-card">
                                {services.map((service) => (
                                    <li
                                        key={service.id}
                                        className="flex items-center gap-3 px-3 py-2 text-sm"
                                    >
                                        <Checkbox
                                            id={`service-${service.id}`}
                                            checked={picked.has(service.id)}
                                            onCheckedChange={(isOn) =>
                                                setPicked((current) =>
                                                    toggle(
                                                        current,
                                                        service.id,
                                                        isOn === true,
                                                    ),
                                                )
                                            }
                                        />
                                        <Label
                                            htmlFor={`service-${service.id}`}
                                            className="min-w-0 flex-1 font-normal"
                                        >
                                            <span className="block truncate">
                                                {service.name}
                                            </span>
                                            <span className="text-xs text-muted-foreground">
                                                {service.rate?.name}
                                                {service.next_invoice &&
                                                    ` · next from ${dateFormatter.format(new Date(service.next_invoice.starts_on))}`}
                                            </span>
                                        </Label>
                                        {service.next_invoice && (
                                            <span className="text-muted-foreground tabular-nums">
                                                {formatCents(
                                                    service.next_invoice
                                                        .total_in_cents,
                                                    currency,
                                                )}
                                            </span>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </fieldset>
                    <Button onClick={verify}>Verify</Button>
                </div>
            )}

            {step === 2 && (
                <div className="space-y-4">
                    {data === null ? (
                        <div className="space-y-2">
                            <Skeleton className="h-9 w-full" />
                            <Skeleton className="h-9 w-full" />
                        </div>
                    ) : lines.length === 0 ? (
                        <p className="rounded-lg border border-dashed px-4 py-6 text-center text-sm text-muted-foreground">
                            Nothing is due for those services by the end of that
                            month, and nothing is waiting.
                        </p>
                    ) : (
                        <>
                            <BillingLines
                                lines={lines}
                                currency={currency}
                                ticked={ticked}
                                onToggle={(key, isOn) =>
                                    setTicked((current) =>
                                        toggle(current, key, isOn),
                                    )
                                }
                            />
                            <p className="text-sm text-muted-foreground">
                                {ticked.size} of {lines.length} lines ·{' '}
                                {formatCents(total, currency)} before tax · due{' '}
                                {dateFormatter.format(new Date(data.due_on))}
                            </p>
                        </>
                    )}
                    <InputError message={error} />
                    <div className="flex gap-2">
                        <Button variant="outline" onClick={() => setStep(1)}>
                            Back
                        </Button>
                        {canInvoice && (
                            <Button
                                disabled={processing || ticked.size === 0}
                                onClick={create}
                            >
                                {processing && <Spinner />}
                                Create invoice
                            </Button>
                        )}
                    </div>
                </div>
            )}

            {step === 3 && invoiceId && (
                <BillingDone
                    title="The invoice was created."
                    actions={
                        <>
                            <Button onClick={() => openInvoice(invoiceId)}>
                                <ExternalLink />
                                Invoice details
                            </Button>
                            <Button variant="outline" onClick={restart}>
                                Bill again
                            </Button>
                        </>
                    }
                >
                    It is a draft. Open it to check it, then send it.
                </BillingDone>
            )}
        </div>
    );
}
