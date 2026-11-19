import { Form, Link } from '@inertiajs/react';
import { useState } from 'react';
import CustomFieldsFormFields from '@/components/custom-fields/custom-fields-form';
import FormField from '@/components/form-field';
import NativeSelect from '@/components/native-select';
import {
    billingFrequencyLabel,
    billingPeriodLabel,
} from '@/components/rates/billing-period';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatCents } from '@/lib/money';
import type { Rate, Service, ServiceFormOptions } from '@/types';

type Action = { action: string; method: 'post' | 'put' };

function rateLabel(rate: Rate): string {
    return `${rate.name} · ${formatCents(rate.price_in_cents)} a month${rate.is_archived ? ' (archived)' : ''}`;
}

/**
 * The service form. A new service picks its rate and status here; an
 * existing one changes them with their own actions, so leaves them out.
 */
export default function ServiceForm({
    action,
    service,
    options,
    submitLabel,
    cancelHref,
}: {
    action: Action;
    service?: Service;
    options: ServiceFormOptions;
    submitLabel: string;
    cancelHref?: string;
}) {
    const isNew = service === undefined;
    const [rateId, setRateId] = useState(
        service?.rate_id ?? options.rates[0]?.id ?? '',
    );
    const [autoRenew, setAutoRenew] = useState(service?.auto_renew ?? false);
    const rate = options.rates.find((option) => option.id === rateId);

    return (
        <Form {...action} className="max-w-2xl space-y-6">
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-6 sm:grid-cols-2">
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
                                autoFocus={isNew}
                            />
                        </FormField>
                        <FormField
                            id="short_name"
                            label="Short name"
                            hint="Unique to the customer, such as a domain. Left blank, it's made from the name."
                            error={errors.short_name}
                        >
                            <Input
                                id="short_name"
                                name="short_name"
                                defaultValue={service?.short_name ?? ''}
                                placeholder="example.com.au"
                            />
                        </FormField>
                    </div>

                    {isNew && (
                        <div className="grid gap-6 sm:grid-cols-2">
                            <FormField
                                id="rate_id"
                                label="Rate"
                                hint={
                                    options.rates.length === 0
                                        ? 'Add a rate under Settings → Rates first.'
                                        : undefined
                                }
                                error={errors.rate_id}
                            >
                                <NativeSelect
                                    id="rate_id"
                                    name="rate_id"
                                    value={rateId}
                                    onChange={(event) =>
                                        setRateId(event.target.value)
                                    }
                                    required
                                >
                                    {options.rates.map((option) => (
                                        <option
                                            key={option.id}
                                            value={option.id}
                                        >
                                            {rateLabel(option)}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </FormField>
                            <FormField
                                id="status"
                                label="Status"
                                error={errors.status}
                            >
                                <NativeSelect
                                    id="status"
                                    name="status"
                                    defaultValue="active"
                                >
                                    {options.statuses.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </FormField>
                        </div>
                    )}

                    <div className="grid gap-6 sm:grid-cols-2">
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
                            id="billing_period_in_months"
                            label="Billed"
                            error={errors.billing_period_in_months}
                        >
                            <NativeSelect
                                id="billing_period_in_months"
                                name="billing_period_in_months"
                                defaultValue={
                                    service?.billing_period_in_months ?? ''
                                }
                            >
                                <option value="">
                                    {rate
                                        ? `As the rate: every ${billingPeriodLabel(rate.billing_period_in_months)}`
                                        : 'As the rate'}
                                </option>
                                {options.billingPeriods.map((months) => (
                                    <option key={months} value={months}>
                                        {billingFrequencyLabel(months)}
                                    </option>
                                ))}
                            </NativeSelect>
                        </FormField>
                        <FormField
                            id="billing_starts_on"
                            label="Billing starts"
                            error={errors.billing_starts_on}
                        >
                            <Input
                                id="billing_starts_on"
                                name="billing_starts_on"
                                type="date"
                                defaultValue={
                                    service?.billing_starts_on ??
                                    new Date().toISOString().slice(0, 10)
                                }
                                required
                            />
                        </FormField>
                        <FormField
                            id="ends_on"
                            label="Expires"
                            hint="Optional, such as a domain's expiry date."
                            error={errors.ends_on}
                        >
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
                                {options.locations.map((location) => (
                                    <option
                                        key={location.id}
                                        value={location.id}
                                    >
                                        {location.name}
                                    </option>
                                ))}
                            </NativeSelect>
                        </FormField>
                        <FormField
                            id="contact_id"
                            label="Contact"
                            error={errors.contact_id}
                        >
                            <NativeSelect
                                id="contact_id"
                                name="contact_id"
                                defaultValue={service?.contact_id ?? ''}
                            >
                                <option value="">No particular contact</option>
                                {options.contacts.map((contact) => (
                                    <option key={contact.id} value={contact.id}>
                                        {contact.name}
                                    </option>
                                ))}
                            </NativeSelect>
                        </FormField>
                    </div>

                    {isNew && (
                        <div className="flex items-start gap-2">
                            <input
                                type="hidden"
                                name="auto_renew"
                                value={autoRenew ? '1' : '0'}
                            />
                            <Checkbox
                                id="auto_renew"
                                checked={autoRenew}
                                onCheckedChange={(checked) =>
                                    setAutoRenew(checked === true)
                                }
                            />
                            <div className="grid gap-0.5">
                                <Label
                                    htmlFor="auto_renew"
                                    className="font-normal"
                                >
                                    Renews by itself
                                </Label>
                                <p className="text-xs text-muted-foreground">
                                    For things that renew with a supplier, such
                                    as a domain name.
                                </p>
                            </div>
                        </div>
                    )}

                    <div className="grid gap-6 empty:hidden sm:grid-cols-2">
                        <CustomFieldsFormFields
                            target="service"
                            values={service?.custom_fields}
                            errors={errors}
                        />
                    </div>
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
                        {cancelHref && (
                            <Button variant="ghost" asChild>
                                <Link href={cancelHref}>Cancel</Link>
                            </Button>
                        )}
                    </div>
                </>
            )}
        </Form>
    );
}
