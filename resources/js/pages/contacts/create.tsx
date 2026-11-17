import { Head } from '@inertiajs/react';
import ContactForm from '@/components/contacts/contact-form';
import { useCreateCustomer } from '@/components/customers/create-customer-dialog';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import StoreContactController from '@/actions/App/Contact/Controllers/StoreContactController';
import { create, index } from '@/routes/contacts';
import { show as showCustomer } from '@/routes/customers';
import type { CustomerOption } from '@/types';

export default function CreateContact({
    customers,
    customerId,
}: {
    customers: CustomerOption[];
    customerId: string | null;
}) {
    return (
        <>
            <Head title="Add contact" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Add contact"
                    description="Add the person you deal with at a customer."
                />
                {customers.length === 0 ? (
                    <EmptyState
                        title="Add a customer first"
                        description="Every contact works for a customer."
                        action={<AddCustomerButton />}
                    />
                ) : (
                    <ContactForm
                        action={StoreContactController.form()}
                        customers={customers}
                        customerId={customerId}
                        submitLabel="Add contact"
                        cancelHref={
                            customerId
                                ? showCustomer(customerId).url
                                : index().url
                        }
                    />
                )}
            </div>
        </>
    );
}

CreateContact.layout = {
    breadcrumbs: [
        { title: 'Contacts', href: index() },
        { title: 'Add contact', href: create() },
    ],
};

function AddCustomerButton() {
    const { openCreateCustomer } = useCreateCustomer();

    return <Button onClick={() => openCreateCustomer()}>Add customer</Button>;
}
