import { Link, router, useHttp } from '@inertiajs/react';
import { useState } from 'react';
import BillingDone from '@/components/billing/billing-done';
import Stepper from '@/components/billing/stepper';
import FormField from '@/components/form-field';
import InputError from '@/components/input-error';
import NativeSelect from '@/components/native-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { formatCents } from '@/lib/money';
import { billing } from '@/routes/customers';
import { servicePeriod } from '@/routes/customers/billing';
import { store } from '@/routes/customers/billing/service-period';
import type { BillingLine, Service } from '@/types';

const STEPS = [
    { title: 'Parameters', description: 'Select service and month' },
    { title: 'Verify', description: 'Verify invoice item' },
    { title: 'Completed', description: 'Billing completed' },
];

type Preview = { line: BillingLine | null; reason: string | null };

/**
 * Single Service Billing, as Amphora's: bill one service's next period for a
 * month as a line that waits to be combined into an invoice.
 */
export default function SingleBilling({
    customerId,
    currency,
    services,
    defaultMonth,
    canCharge,
}: {
    customerId: string;
    currency: string;
    services: Service[];
    defaultMonth: string;
    canCharge: boolean;
}) {
    const [step, setStep] = useState(1);
    const [serviceId, setServiceId] = useState(services[0]?.id ?? '');
    const [month, setMonth] = useState(defaultMonth);
    const [result, setResult] = useState<Preview | null>(null);
    const [error, setError] = useState<string | undefined>();
    const [processing, setProcessing] = useState(false);
    const http = useHttp<Record<string, never>, Preview>();

    const verify = () => {
        setResult(null);
        setError(undefined);
        setStep(2);
        void http
            .get(
                servicePeriod.url(customerId, {
                    query: { service: serviceId, month },
                }),
            )
            .then(setResult)
            .catch(() => undefined);
    };

    const add = () =>
        router.post(
            store.url(customerId),
            { service_id: serviceId, month },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (errors) => setError(Object.values(errors)[0]),
                onSuccess: () => setStep(3),
            },
        );

    if (services.length === 0) {
        return (
            <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                They have no active services on a rate to bill.
            </p>
        );
    }

    return (
        <div>
            <Stepper steps={STEPS} current={step} />

            {step === 1 && (
                <div className="max-w-md space-y-5">
                    <FormField id="single-service" label="Service">
                        <NativeSelect
                            id="single-service"
                            value={serviceId}
                            onChange={(event) =>
                                setServiceId(event.target.value)
                            }
                        >
                            {services.map((service) => (
                                <option key={service.id} value={service.id}>
                                    {service.name}
                                    {service.rate
                                        ? ` · ${service.rate.name}`
                                        : ''}
                                </option>
                            ))}
                        </NativeSelect>
                    </FormField>
                    <FormField id="single-month" label="Month">
                        <Input
                            id="single-month"
                            type="month"
                            value={month}
                            onChange={(event) =>
                                event.target.value &&
                                setMonth(event.target.value)
                            }
                            className="max-w-48"
                        />
                    </FormField>
                    <Button disabled={!serviceId} onClick={verify}>
                        Verify
                    </Button>
                </div>
            )}

            {step === 2 && (
                <div className="space-y-4">
                    {result === null ? (
                        <Skeleton className="h-12 w-full" />
                    ) : result.line ? (
                        <div className="flex items-center gap-3 rounded-lg border bg-card px-3 py-2.5 text-sm">
                            <div className="min-w-0 flex-1">
                                <p className="truncate font-medium">
                                    {result.line.description}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {result.line.quantity} ×{' '}
                                    {formatCents(
                                        result.line.unit_price_in_cents,
                                        currency,
                                    )}
                                </p>
                            </div>
                            <span className="font-medium tabular-nums">
                                {formatCents(
                                    result.line.total_in_cents,
                                    currency,
                                )}
                            </span>
                        </div>
                    ) : (
                        <p className="rounded-lg border border-dashed px-4 py-6 text-center text-sm text-muted-foreground">
                            {result.reason}
                        </p>
                    )}
                    <InputError message={error} />
                    <div className="flex gap-2">
                        <Button variant="outline" onClick={() => setStep(1)}>
                            Back
                        </Button>
                        {canCharge && result?.line && (
                            <Button disabled={processing} onClick={add}>
                                {processing && <Spinner />}
                                Add to waiting lines
                            </Button>
                        )}
                    </div>
                </div>
            )}

            {step === 3 && (
                <BillingDone
                    title="The service period is waiting to be invoiced."
                    actions={
                        <>
                            <Button asChild>
                                <Link
                                    href={billing({
                                        customer: customerId,
                                        page: 'combine',
                                    })}
                                >
                                    Combine waiting lines
                                </Link>
                            </Button>
                            <Button
                                variant="outline"
                                onClick={() => setStep(1)}
                            >
                                Bill another
                            </Button>
                        </>
                    }
                >
                    It goes on their next invoice: combine it now, or the
                    monthly run picks it up.
                </BillingDone>
            )}
        </div>
    );
}
