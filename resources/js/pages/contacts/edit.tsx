import { Head, setLayoutProps } from '@inertiajs/react';
import ConfirmDelete from '@/components/confirm-delete';
import ContactForm from '@/components/contacts/contact-form';
import PageHeader from '@/components/page-header';
import DeleteContactController from '@/actions/App/Contact/Controllers/DeleteContactController';
import UpdateContactController from '@/actions/App/Contact/Controllers/UpdateContactController';
import { edit, index } from '@/routes/contacts';
import { show as showCustomer } from '@/routes/customers';
import type { Contact, CustomerOption } from '@/types';

export default function EditContact({
    contact,
    customers,
}: {
    contact: Contact;
    customers: CustomerOption[];
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Contacts', href: index() },
            { title: contact.full_name, href: edit(contact.id) },
        ],
    });

    return (
        <>
            <Head title={`Edit ${contact.full_name}`} />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={`Edit ${contact.full_name}`}
                    actions={
                        <ConfirmDelete
                            action={DeleteContactController.form(contact.id)}
                            title={`Delete ${contact.full_name}?`}
                            description="This can't be undone."
                        />
                    }
                />
                <ContactForm
                    action={UpdateContactController.form(contact.id)}
                    contact={contact}
                    customers={customers}
                    submitLabel="Save"
                    cancelHref={showCustomer(contact.customer_id).url}
                />
            </div>
        </>
    );
}
