import { Head, setLayoutProps } from '@inertiajs/react';
import CustomerForm from '@/components/customers/customer-form';
import PageHeader from '@/components/page-header';
import UpdateCustomerController from '@/actions/App/Customer/Controllers/UpdateCustomerController';
import { edit, index, show } from '@/routes/customers';
import type { Customer, Option } from '@/types';

export default function EditCustomer({
    customer,
    statuses,
}: {
    customer: Customer;
    statuses: Option[];
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Customers', href: index() },
            { title: customer.name, href: show(customer.id) },
            { title: 'Edit', href: edit(customer.id) },
        ],
    });

    return (
        <>
            <Head title={`Edit ${customer.name}`} />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader title={`Edit ${customer.name}`} />
                <CustomerForm
                    action={UpdateCustomerController.form(customer.id)}
                    customer={customer}
                    statuses={statuses}
                    submitLabel="Save"
                    cancelHref={show(customer.id).url}
                />
            </div>
        </>
    );
}
