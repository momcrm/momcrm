import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Check } from 'lucide-react';
import type { FormEvent } from 'react';
import ConfirmDelete from '@/components/confirm-delete';
import { PrefixCode } from '@/components/customers/customer-meta';
import { TabHeader } from '@/components/customers/workspace-tab';
import NativeSelect from '@/components/native-select';
import { SettingsRow, SettingsSection } from '@/components/settings-section';
import { ChoiceTag } from '@/components/tickets/ticket-meta';
import { useCustomerTypes } from '@/components/customers/customer-meta';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import DeleteCustomerController from '@/actions/App/Customer/Controllers/DeleteCustomerController';
import { useWorkspaceBreadcrumbs } from '@/layouts/customer/layout';
import { create as createContact } from '@/routes/contacts';
import { edit, update } from '@/routes/customers';
import { create as createLocation } from '@/routes/locations';
import { edit as editWorkflow } from '@/routes/workflow';
import type {
    CustomerBillingType,
    CustomerOption,
    CustomerWorkspace,
    Option,
} from '@/types';

const billingDescriptions: Record<CustomerBillingType, string> = {
    automatic: 'For customers whose invoices go out without review.',
    manual: 'For customers whose invoices someone checks first.',
    excluded: "For customers you don't bill.",
};

/**
 * One choice among a few, as a row of buttons, such as a customer's status.
 */
function Segmented({
    label,
    options,
    value,
    onChange,
}: {
    label: string;
    options: Option[];
    value: string;
    onChange: (value: string) => void;
}) {
    return (
        <ToggleGroup
            type="single"
            variant="segmented"
            aria-label={label}
            value={value}
            onValueChange={(next) => next && onChange(next)}
            className="w-fit"
        >
            {options.map((option) => (
                <ToggleGroupItem
                    key={option.value}
                    value={option.value}
                    className="h-7 px-3 text-[13px]"
                >
                    {option.label}
                </ToggleGroupItem>
            ))}
        </ToggleGroup>
    );
}

export default function CustomerSettings({
    workspace,
    statuses,
    billingTypes,
    contacts,
    locations,
    mainContactId,
    mainLocationId,
}: {
    workspace: CustomerWorkspace;
    statuses: Option[];
    billingTypes: Option[];
    contacts: CustomerOption[];
    locations: CustomerOption[];
    mainContactId: string | null;
    mainLocationId: string | null;
}) {
    const { customer, can } = workspace;
    const { canManageWorkflow } = usePage().props;
    useWorkspaceBreadcrumbs(customer, {
        title: 'Settings',
        href: edit(customer.id),
    });

    const form = useForm({
        name: customer.name,
        prefix: customer.prefix ?? '',
        type_id: customer.type_id ?? '',
        status: customer.status as string,
        billing_type: customer.billing_type as string,
        email: customer.email ?? '',
        phone: customer.phone ?? '',
        website: customer.website ?? '',
        abn: customer.abn ?? '',
        notes: customer.notes ?? '',
        main_contact_id: mainContactId ?? '',
        main_location_id: mainLocationId ?? '',
    });
    const types = useCustomerTypes(form.data.type_id);
    const type = types.find((choice) => choice.id === form.data.type_id);
    const canEdit = Boolean(can.update);

    const save = (event: FormEvent) => {
        event.preventDefault();
        form.put(update.url(customer.id), {
            preserveScroll: true,
            onSuccess: () => form.setDefaults(),
        });
    };

    const text = (field: keyof typeof form.data) => ({
        id: field,
        value: form.data[field],
        onChange: (event: { target: { value: string } }) =>
            form.setData(field, event.target.value),
        disabled: !canEdit,
    });

    return (
        <>
            <Head title={`${customer.name} · Settings`} />
            <TabHeader
                title="Settings"
                description="How this customer is named, billed and reached."
            />

            <form onSubmit={save} className="max-w-3xl space-y-5 pb-4">
                <SettingsSection
                    title="Identity"
                    description={
                        <span className="flex flex-wrap items-center gap-1.5">
                            Shown across MomCRM as
                            <span className="inline-flex items-center gap-1.5 rounded-md border bg-card px-1.5 py-0.5 text-foreground">
                                <PrefixCode
                                    prefix={form.data.prefix.toUpperCase()}
                                />
                                <span className="font-medium">
                                    {form.data.name || 'Untitled'}
                                </span>
                                {type && (
                                    <ChoiceTag
                                        choice={type}
                                        className="text-muted-foreground"
                                    />
                                )}
                            </span>
                        </span>
                    }
                >
                    <SettingsRow
                        label="Name"
                        htmlFor="name"
                        description="The business or person you work for."
                        error={form.errors.name}
                    >
                        <Input {...text('name')} required />
                    </SettingsRow>
                    <SettingsRow
                        label="Prefix"
                        htmlFor="prefix"
                        description="A short code to file them under, up to 8 letters. Found in search."
                        error={form.errors.prefix}
                    >
                        <Input
                            {...text('prefix')}
                            maxLength={8}
                            placeholder="ACME"
                            className="w-32 font-mono uppercase"
                        />
                    </SettingsRow>
                    <SettingsRow
                        label="Type"
                        htmlFor="type_id"
                        description={
                            <>
                                Your team's own list.
                                {canManageWorkflow && (
                                    <>
                                        {' '}
                                        <Link
                                            href={editWorkflow()}
                                            className="underline underline-offset-2 hover:text-foreground"
                                        >
                                            Edit types
                                        </Link>
                                    </>
                                )}
                            </>
                        }
                        error={form.errors.type_id}
                    >
                        <NativeSelect {...text('type_id')}>
                            <option value="">No type</option>
                            {types.map((choice) => (
                                <option key={choice.id} value={choice.id}>
                                    {choice.name}
                                </option>
                            ))}
                        </NativeSelect>
                    </SettingsRow>
                </SettingsSection>

                <SettingsSection
                    title="Status and billing"
                    description="Whether you're working for them now, and how they're invoiced."
                >
                    <SettingsRow
                        label="Status"
                        description="Inactive customers stay on record, marked as inactive."
                        error={form.errors.status}
                    >
                        <Segmented
                            label="Status"
                            options={statuses}
                            value={form.data.status}
                            onChange={(value) => form.setData('status', value)}
                        />
                    </SettingsRow>
                    <SettingsRow
                        label="Billing"
                        description={
                            billingDescriptions[
                                form.data.billing_type as CustomerBillingType
                            ]
                        }
                        error={form.errors.billing_type}
                    >
                        <Segmented
                            label="Billing"
                            options={billingTypes}
                            value={form.data.billing_type}
                            onChange={(value) =>
                                form.setData('billing_type', value)
                            }
                        />
                    </SettingsRow>
                </SettingsSection>

                <SettingsSection
                    title="Contact details"
                    description="How to reach them as a business. People go under Contacts."
                >
                    <SettingsRow
                        label="Email"
                        htmlFor="email"
                        error={form.errors.email}
                    >
                        <Input
                            {...text('email')}
                            type="email"
                            placeholder="accounts@example.com"
                        />
                    </SettingsRow>
                    <SettingsRow
                        label="Phone"
                        htmlFor="phone"
                        error={form.errors.phone}
                    >
                        <Input {...text('phone')} type="tel" />
                    </SettingsRow>
                    <SettingsRow
                        label="Website"
                        htmlFor="website"
                        error={form.errors.website}
                    >
                        <Input
                            {...text('website')}
                            type="url"
                            placeholder="https://"
                        />
                    </SettingsRow>
                    <SettingsRow
                        label="ABN"
                        htmlFor="abn"
                        error={form.errors.abn}
                    >
                        <Input {...text('abn')} className="w-48" />
                    </SettingsRow>
                </SettingsSection>

                <SettingsSection
                    title="Main contact and location"
                    description="Marked as the main ones on the Contacts and Locations tabs."
                >
                    <SettingsRow
                        label="Main contact"
                        htmlFor="main_contact_id"
                        description="Who to call first."
                        error={form.errors.main_contact_id}
                    >
                        {contacts.length > 0 ? (
                            <NativeSelect {...text('main_contact_id')}>
                                <option value="">None</option>
                                {contacts.map((contact) => (
                                    <option key={contact.id} value={contact.id}>
                                        {contact.name}
                                    </option>
                                ))}
                            </NativeSelect>
                        ) : (
                            <Button variant="outline" size="sm" asChild>
                                <Link
                                    href={createContact({
                                        query: { customer: customer.id },
                                    })}
                                >
                                    Add a contact
                                </Link>
                            </Button>
                        )}
                    </SettingsRow>
                    <SettingsRow
                        label="Main location"
                        htmlFor="main_location_id"
                        description="Their head office or main site."
                        error={form.errors.main_location_id}
                    >
                        {locations.length > 0 ? (
                            <NativeSelect {...text('main_location_id')}>
                                <option value="">None</option>
                                {locations.map((location) => (
                                    <option
                                        key={location.id}
                                        value={location.id}
                                    >
                                        {location.name}
                                    </option>
                                ))}
                            </NativeSelect>
                        ) : (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={createLocation(customer.id)}>
                                    Add a location
                                </Link>
                            </Button>
                        )}
                    </SettingsRow>
                </SettingsSection>

                <SettingsSection
                    title="Notes"
                    description="Anything your team should know, such as how they like to be contacted."
                >
                    <div className="p-4">
                        <Textarea
                            {...text('notes')}
                            aria-label="Notes"
                            rows={4}
                        />
                        {form.errors.notes && (
                            <p className="mt-1.5 text-sm text-destructive">
                                {form.errors.notes}
                            </p>
                        )}
                    </div>
                </SettingsSection>

                {canEdit && (
                    <div className="sticky bottom-4 z-10 flex items-center justify-between gap-3 rounded-xl border bg-card/95 px-4 py-2.5 shadow-pop backdrop-blur">
                        <span className="flex items-center gap-1.5 text-[13px] text-muted-foreground">
                            {form.isDirty ? (
                                <>
                                    <span className="size-1.5 rounded-full bg-warning" />
                                    Unsaved changes
                                </>
                            ) : form.recentlySuccessful ? (
                                <>
                                    <Check className="size-3.5 text-success" />
                                    Saved
                                </>
                            ) : (
                                'All changes saved'
                            )}
                        </span>
                        <div className="flex gap-2">
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                disabled={!form.isDirty || form.processing}
                                onClick={() => {
                                    form.reset();
                                    form.clearErrors();
                                }}
                            >
                                Discard
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                disabled={!form.isDirty || form.processing}
                                data-test="save-customer-button"
                            >
                                {form.processing && <Spinner />}
                                Save changes
                            </Button>
                        </div>
                    </div>
                )}
            </form>

            {can.delete && (
                <div className="mt-5 max-w-3xl">
                    <SettingsSection title="Danger zone" tone="destructive">
                        <div className="flex flex-wrap items-center justify-between gap-4 px-4 py-3.5">
                            <div className="min-w-0 flex-1 basis-64">
                                <p className="text-sm font-medium">
                                    Delete this customer
                                </p>
                                <p className="mt-0.5 text-xs text-muted-foreground">
                                    Their contacts, locations, assets, services,
                                    contracts, files, secrets, tickets and
                                    invoices go too. This can't be undone.
                                </p>
                            </div>
                            <ConfirmDelete
                                action={DeleteCustomerController.form(
                                    customer.id,
                                )}
                                title={`Delete ${customer.name}?`}
                                description="Their contacts, locations, assets, services, contracts, files, secrets, tickets and invoices are deleted too. This can't be undone."
                                trigger="Delete customer"
                            />
                        </div>
                    </SettingsSection>
                </div>
            )}
        </>
    );
}
