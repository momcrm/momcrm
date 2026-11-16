import { Head } from '@inertiajs/react';
import ContactTable from '@/components/contacts/contact-table';
import { AddLink, TabHeader } from '@/components/customers/workspace-tab';
import EmptyState from '@/components/empty-state';
import { useWorkspaceBreadcrumbs } from '@/layouts/customer/layout';
import { create } from '@/routes/contacts';
import { contacts as contactsTab } from '@/routes/customers';
import type { Contact, CustomerWorkspace } from '@/types';

export default function CustomerContacts({
    workspace,
    contacts,
}: {
    workspace: CustomerWorkspace;
    contacts: Contact[];
}) {
    const { customer } = workspace;
    useWorkspaceBreadcrumbs(customer, {
        title: 'Contacts',
        href: contactsTab(customer.id),
    });
    const add = (
        <AddLink
            href={create({ query: { customer: customer.id } }).url}
            label="Add contact"
        />
    );

    return (
        <>
            <Head title={`${customer.name} · Contacts`} />
            <TabHeader
                title="Contacts"
                description="The people you deal with. The main contact is listed first."
                action={contacts.length > 0 && add}
            />
            {contacts.length > 0 ? (
                <ContactTable contacts={contacts} showCustomer={false} />
            ) : (
                <EmptyState
                    title="No contacts yet"
                    description="Add the people you deal with at this customer, so you know who to call."
                    action={add}
                />
            )}
        </>
    );
}
