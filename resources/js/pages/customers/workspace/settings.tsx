import { Head } from '@inertiajs/react';
import ConfirmDelete from '@/components/confirm-delete';
import CustomerForm from '@/components/customers/customer-form';
import { TabHeader } from '@/components/customers/workspace-tab';
import FormField from '@/components/form-field';
import NativeSelect from '@/components/native-select';
import DeleteCustomerController from '@/actions/App/Customer/Controllers/DeleteCustomerController';
import UpdateCustomerController from '@/actions/App/Customer/Controllers/UpdateCustomerController';
import { useWorkspaceBreadcrumbs } from '@/layouts/customer/layout';
import { edit } from '@/routes/customers';
import type { CustomerOption, CustomerWorkspace, Option } from '@/types';

function MainPicker({
    id,
    label,
    hint,
    none,
    options,
    value,
    error,
}: {
    id: string;
    label: string;
    hint: string;
    none: string;
    options: CustomerOption[];
    value: string | null;
    error?: string;
}) {
    return (
        <FormField id={id} label={label} hint={hint} error={error}>
            <NativeSelect
                id={id}
                name={id}
                defaultValue={value ?? ''}
                disabled={options.length === 0}
            >
                <option value="">{options.length === 0 ? none : 'None'}</option>
                {options.map((option) => (
                    <option key={option.id} value={option.id}>
                        {option.name}
                    </option>
                ))}
            </NativeSelect>
        </FormField>
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
    useWorkspaceBreadcrumbs(customer, {
        title: 'Settings',
        href: edit(customer.id),
    });

    return (
        <>
            <Head title={`${customer.name} · Settings`} />
            <TabHeader title="Settings" />

            <div className="max-w-2xl space-y-10">
                <CustomerForm
                    action={UpdateCustomerController.form(customer.id)}
                    customer={customer}
                    statuses={statuses}
                    billingTypes={billingTypes}
                    submitLabel="Save"
                >
                    {(errors) => (
                        <div className="grid gap-6 sm:grid-cols-2">
                            <MainPicker
                                id="main_contact_id"
                                label="Main contact"
                                hint="Listed first, and who to call."
                                none="Add a contact first"
                                options={contacts}
                                value={mainContactId}
                                error={errors.main_contact_id}
                            />
                            <MainPicker
                                id="main_location_id"
                                label="Main location"
                                hint="Their head office or main site."
                                none="Add a location first"
                                options={locations}
                                value={mainLocationId}
                                error={errors.main_location_id}
                            />
                        </div>
                    )}
                </CustomerForm>

                {can.delete && (
                    <section className="space-y-3 rounded-lg border border-destructive/30 p-4">
                        <div>
                            <h3 className="font-medium">Delete customer</h3>
                            <p className="text-sm text-muted-foreground">
                                Their contacts, locations, assets, services,
                                tickets and invoices are deleted too. This can't
                                be undone.
                            </p>
                        </div>
                        <ConfirmDelete
                            action={DeleteCustomerController.form(customer.id)}
                            title={`Delete ${customer.name}?`}
                            description="Their contacts, locations, assets, services, tickets and invoices are deleted too. This can't be undone."
                        />
                    </section>
                )}
            </div>
        </>
    );
}
