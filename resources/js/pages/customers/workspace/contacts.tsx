import { Head } from '@inertiajs/react';
import ContactTable from '@/components/contacts/contact-table';
import { TabHeader } from '@/components/customers/workspace-tab';
import { useCreateContact } from '@/components/contacts/create-contact-dialog';
import EmptyState from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import { useWorkspaceBreadcrumbs } from '@/layouts/customer/layout';
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
    const { openCreateContact } = useCreateContact();
    const add = (
        <Button
            variant="outline"
            size="sm"
            onClick={() => openCreateContact({ customerId: customer.id })}
        >
            Add contact
        </Button>
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
