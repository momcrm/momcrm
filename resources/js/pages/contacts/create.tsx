import { Head } from '@inertiajs/react';
import ContactForm from '@/components/contacts/contact-form';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import StoreContactController from '@/actions/App/Contact/Controllers/StoreContactController';
import { create, index } from '@/routes/contacts';
import {
    create as createCustomer,
    show as showCustomer,
} from '@/routes/customers';
import type { CustomerOption } from '@/types';
import { Link } from '@inertiajs/react';

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
                        action={
                            <Button asChild>
                                <Link href={createCustomer()}>
                                    Add customer
                                </Link>
                            </Button>
                        }
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
