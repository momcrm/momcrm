import { Head, Link, setLayoutProps } from '@inertiajs/react';
import ConfirmDelete from '@/components/confirm-delete';
import ContactList from '@/components/contacts/contact-list';
import DetailSection from '@/components/detail-section';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import DeleteCustomerController from '@/actions/App/Customer/Controllers/DeleteCustomerController';
import { create as createContact } from '@/routes/contacts';
import { edit, index, show } from '@/routes/customers';
import type { Contact, Customer } from '@/types';

export default function ShowCustomer({
    customer,
    contacts,
}: {
    customer: Customer;
    contacts: Contact[];
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Customers', href: index() },
            { title: customer.name, href: show(customer.id) },
        ],
    });

    const details = [
        { label: 'Email', value: customer.email },
        { label: 'Phone', value: customer.phone },
        { label: 'Website', value: customer.website },
        { label: 'ABN', value: customer.abn },
    ].filter((detail) => detail.value);

    const addContact = (
        <Button variant="outline" size="sm" asChild>
            <Link href={createContact({ query: { customer: customer.id } })}>
                Add contact
            </Link>
        </Button>
    );

    return (
        <>
            <Head title={customer.name} />

            <div className="flex flex-col gap-8 p-4 md:p-6">
                <PageHeader
                    title={customer.name}
                    description={
                        <span className="flex items-center gap-2">
                            Customer #{customer.number}
                            {customer.status === 'inactive' && (
                                <Badge variant="secondary">Inactive</Badge>
                            )}
                            {customer.is_sample && (
                                <Badge variant="outline">Sample</Badge>
                            )}
                        </span>
                    }
                    actions={
                        <>
                            <Button variant="outline" asChild>
                                <Link href={edit(customer.id)}>Edit</Link>
                            </Button>
                            <ConfirmDelete
                                action={DeleteCustomerController.form(
                                    customer.id,
                                )}
                                title={`Delete ${customer.name}?`}
                                description="Their contacts, locations, assets, services, tickets and invoices are deleted too. This can't be undone."
                            />
                        </>
                    }
                />

                {(details.length > 0 || customer.notes) && (
                    <dl className="grid gap-4 rounded-lg border p-4 sm:grid-cols-2">
                        {details.map((detail) => (
                            <div key={detail.label}>
                                <dt className="text-sm text-muted-foreground">
                                    {detail.label}
                                </dt>
                                <dd className="break-words">{detail.value}</dd>
                            </div>
                        ))}
                        {customer.notes && (
                            <div className="sm:col-span-2">
                                <dt className="text-sm text-muted-foreground">
                                    Notes
                                </dt>
                                <dd className="whitespace-pre-line">
                                    {customer.notes}
                                </dd>
                            </div>
                        )}
                    </dl>
                )}

                <DetailSection
                    title="Contacts"
                    action={contacts.length > 0 && addContact}
                >
                    {contacts.length > 0 ? (
                        <ContactList contacts={contacts} />
                    ) : (
                        <EmptyState
                            title="No contacts yet"
                            description="Add the people you deal with at this customer, so you know who to call."
                            action={addContact}
                        />
                    )}
                </DetailSection>
            </div>
        </>
    );
}
