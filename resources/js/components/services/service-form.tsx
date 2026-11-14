import FormField from '@/components/form-field';
import NativeSelect from '@/components/native-select';
import type { CustomerOption, Option, Service } from '@/types';
import { Button } from '@/components/ui/button';
import { Form, Link } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

type Action = { action: string; method: 'post' | 'put' };

export default function ServiceForm({
    action,
    service,
    statuses,
    locations,
    submitLabel,
    cancelHref,
}: {
    action: Action;
    service?: Service;
    statuses: Option[];
    locations: CustomerOption[];
    submitLabel: string;
    cancelHref: string;
}) {
    return (
        <Form {...action} className="max-w-2xl space-y-6">
            {({ processing, errors }) => (
                <>
                    <FormField
                        id="name"
                        label="Name"
                        hint="What this customer gets from you, for example Managed IT support."
                        error={errors.name}
                    >
                        <Input
                            id="name"
                            name="name"
                            defaultValue={service?.name ?? ''}
                            required
                            autoFocus
                        />
                    </FormField>
                    <FormField id="status" label="Status" error={errors.status}>
                        <NativeSelect
                            id="status"
                            name="status"
                            defaultValue={service?.status ?? 'active'}
                        >
                            {statuses.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </NativeSelect>
                    </FormField>
                    <FormField
                        id="quantity"
                        label="Quantity"
                        error={errors.quantity}
                    >
                        <Input
                            id="quantity"
                            name="quantity"
                            type="number"
                            min="1"
                            defaultValue={service?.quantity ?? 1}
                            required
                        />
                    </FormField>
                    <FormField
                        id="unit_price"
                        label="Price each (AUD, ex GST)"
                        error={errors.unit_price_in_cents}
                    >
                        <Input
                            id="unit_price"
                            name="unit_price"
                            type="number"
                            min="0"
                            step="0.01"
                            inputMode="decimal"
                            defaultValue={
                                service?.unit_price_in_cents != null
                                    ? (
                                          service.unit_price_in_cents / 100
                                      ).toFixed(2)
                                    : ''
                            }
                        />
                    </FormField>
                    <FormField
                        id="billing_period_in_months"
                        label="Billed every (months)"
                        hint="1, 3, 6 or 12 months."
                        error={errors.billing_period_in_months}
                    >
                        <Input
                            id="billing_period_in_months"
                            name="billing_period_in_months"
                            type="number"
                            min="1"
                            defaultValue={
                                service?.billing_period_in_months ?? 1
                            }
                            required
                        />
                    </FormField>
                    <FormField
                        id="starts_on"
                        label="Starts"
                        error={errors.starts_on}
                    >
                        <Input
                            id="starts_on"
                            name="starts_on"
                            type="date"
                            defaultValue={service?.starts_on ?? ''}
                        />
                    </FormField>
                    <FormField id="ends_on" label="Ends" error={errors.ends_on}>
                        <Input
                            id="ends_on"
                            name="ends_on"
                            type="date"
                            defaultValue={service?.ends_on ?? ''}
                        />
                    </FormField>
                    <FormField
                        id="location_id"
                        label="Location"
                        error={errors.location_id}
                    >
                        <NativeSelect
                            id="location_id"
                            name="location_id"
                            defaultValue={service?.location_id ?? ''}
                        >
                            <option value="">No particular location</option>
                            {locations.map((location) => (
                                <option key={location.id} value={location.id}>
                                    {location.name}
                                </option>
                            ))}
                        </NativeSelect>
                    </FormField>
                    <FormField id="note" label="Note" error={errors.note}>
                        <Textarea
                            id="note"
                            name="note"
                            rows={3}
                            defaultValue={service?.note ?? ''}
                        />
                    </FormField>

                    <div className="flex items-center gap-2">
                        <Button
                            disabled={processing}
                            data-test="save-service-button"
                        >
                            {processing && <Spinner />}
                            {submitLabel}
                        </Button>
                        <Button variant="ghost" asChild>
                            <Link href={cancelHref}>Cancel</Link>
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
