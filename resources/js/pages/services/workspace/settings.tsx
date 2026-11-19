import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import {
    pickCustomFieldValues,
    useCustomFields,
} from '@/components/custom-fields/custom-field-input';
import CustomFieldsFormFields from '@/components/custom-fields/custom-fields-form';
import { TabHeader } from '@/components/customers/workspace-tab';
import NativeSelect from '@/components/native-select';
import {
    billingFrequencyLabel,
    billingPeriodLabel,
} from '@/components/rates/billing-period';
import {
    ChangeRateSection,
    ServiceLifecycleSections,
} from '@/components/services/service-manage';
import { SettingsRow, SettingsSection } from '@/components/settings-section';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import UnsavedChangesBar, {
    countChanges,
} from '@/components/unsaved-changes-bar';
import { useServiceBreadcrumbs } from '@/layouts/service/layout';
import { edit, update } from '@/routes/services';
import type { ServiceFormOptions, ServiceWorkspace } from '@/types';

export default function ServiceSettings({
    workspace,
    ...options
}: ServiceFormOptions & {
    workspace: ServiceWorkspace;
}) {
    const { service, can } = workspace;
    useServiceBreadcrumbs(workspace, {
        title: 'Settings',
        href: edit(service.id),
    });

    const customFields = useCustomFields('service');
    const form = useForm({
        name: service.name,
        short_name: service.short_name ?? '',
        quantity: String(service.quantity ?? 1),
        billing_period_in_months: service.billing_period_in_months
            ? String(service.billing_period_in_months)
            : '',
        billing_starts_on: service.billing_starts_on ?? '',
        ends_on: service.ends_on ?? '',
        location_id: service.location_id ?? '',
        contact_id: service.contact_id ?? '',
        note: service.note ?? '',
        custom_fields: pickCustomFieldValues(
            customFields,
            service.custom_fields,
        ),
    });
    const [saved, setSaved] = useState(form.data);
    const canEdit = Boolean(can.update);
    const ratePeriod = service.rate?.billing_period_in_months ?? null;

    const save = (event: FormEvent) => {
        event.preventDefault();
        form.put(update.url(service.id), {
            preserveScroll: true,
            onSuccess: () => {
                form.setDefaults();
                setSaved(form.data);
            },
        });
    };

    const text = (field: Exclude<keyof typeof form.data, 'custom_fields'>) => ({
        id: field,
        value: form.data[field],
        onChange: (event: { target: { value: string } }) =>
            form.setData(field, event.target.value),
        disabled: !canEdit,
    });

    return (
        <>
            <Head title={`${service.name} · Settings`} />
            <TabHeader
                title="Settings"
                description="How this service is named and billed, and who it's for."
            />

            <form onSubmit={save} className="space-y-5">
                <SettingsSection
                    title="Identity"
                    description="How the service shows across MomCRM."
                >
                    <SettingsRow
                        label="Name"
                        htmlFor="name"
                        description="What the customer gets from you, such as Managed IT support."
                        error={form.errors.name}
                    >
                        <Input {...text('name')} required />
                    </SettingsRow>
                    <SettingsRow
                        label="Short name"
                        htmlFor="short_name"
                        description="Unique to the customer, such as a domain. Left blank, it's made from the name."
                        error={form.errors.short_name}
                    >
                        <Input
                            {...text('short_name')}
                            placeholder="example.com.au"
                            className="font-mono"
                        />
                    </SettingsRow>
                </SettingsSection>

                <SettingsSection
                    title="Billing"
                    description="How much of it the customer has, and when and how often it's billed."
                >
                    <SettingsRow
                        label="Quantity"
                        htmlFor="quantity"
                        description="Times the rate's price."
                        error={form.errors.quantity}
                    >
                        <Input
                            {...text('quantity')}
                            type="number"
                            min="1"
                            className="w-28"
                            required
                        />
                    </SettingsRow>
                    <SettingsRow
                        label="Billed"
                        htmlFor="billing_period_in_months"
                        description="How many months each invoice covers."
                        error={form.errors.billing_period_in_months}
                    >
                        <NativeSelect {...text('billing_period_in_months')}>
                            <option value="">
                                {ratePeriod
                                    ? `As the rate: every ${billingPeriodLabel(ratePeriod)}`
                                    : 'As the rate'}
                            </option>
                            {options.billingPeriods.map((months) => (
                                <option key={months} value={months}>
                                    {billingFrequencyLabel(months)}
                                </option>
                            ))}
                        </NativeSelect>
                    </SettingsRow>
                    <SettingsRow
                        label="Billing starts"
                        htmlFor="billing_starts_on"
                        description="The first day it's billed for."
                        error={form.errors.billing_starts_on}
                    >
                        <Input
                            {...text('billing_starts_on')}
                            type="date"
                            className="w-44"
                            required
                        />
                    </SettingsRow>
                    <SettingsRow
                        label="Expires"
                        htmlFor="ends_on"
                        description="Optional, such as a domain's expiry date."
                        error={form.errors.ends_on}
                    >
                        <Input
                            {...text('ends_on')}
                            type="date"
                            className="w-44"
                        />
                    </SettingsRow>
                </SettingsSection>

                <SettingsSection
                    title="Where and who"
                    description="The customer's site the service is at, and who to talk to about it."
                >
                    <SettingsRow
                        label="Location"
                        htmlFor="location_id"
                        error={form.errors.location_id}
                    >
                        <NativeSelect {...text('location_id')}>
                            <option value="">No particular location</option>
                            {options.locations.map((location) => (
                                <option key={location.id} value={location.id}>
                                    {location.name}
                                </option>
                            ))}
                        </NativeSelect>
                    </SettingsRow>
                    <SettingsRow
                        label="Contact"
                        htmlFor="contact_id"
                        error={form.errors.contact_id}
                    >
                        <NativeSelect {...text('contact_id')}>
                            <option value="">No particular contact</option>
                            {options.contacts.map((contact) => (
                                <option key={contact.id} value={contact.id}>
                                    {contact.name}
                                </option>
                            ))}
                        </NativeSelect>
                    </SettingsRow>
                </SettingsSection>

                {customFields.length > 0 && (
                    <SettingsSection
                        title="Custom fields"
                        description="Fields your team has added to services."
                    >
                        <div className="grid gap-5 p-4 sm:grid-cols-2">
                            <CustomFieldsFormFields
                                target="service"
                                values={form.data.custom_fields}
                                onChange={(values) =>
                                    form.setData('custom_fields', values)
                                }
                                errors={form.errors}
                                disabled={!canEdit}
                            />
                        </div>
                    </SettingsSection>
                )}

                <SettingsSection
                    title="Note"
                    description="Anything your team should know about this service."
                >
                    <div className="p-4">
                        <Textarea
                            {...text('note')}
                            aria-label="Note"
                            rows={4}
                        />
                        {form.errors.note && (
                            <p className="mt-1.5 text-sm text-destructive">
                                {form.errors.note}
                            </p>
                        )}
                    </div>
                </SettingsSection>

                {canEdit && (
                    <UnsavedChangesBar
                        count={countChanges(form.data, saved)}
                        processing={form.processing}
                        onReset={() => {
                            form.reset();
                            form.clearErrors();
                        }}
                    />
                )}
            </form>

            <div className="mt-5 space-y-5">
                {canEdit && (
                    <ChangeRateSection
                        service={service}
                        rates={options.rates}
                    />
                )}
                <ServiceLifecycleSections service={service} can={can} />
            </div>
        </>
    );
}
