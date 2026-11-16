import { Head } from '@inertiajs/react';
import CustomerForm from '@/components/customers/customer-form';
import PageHeader from '@/components/page-header';
import StoreCustomerController from '@/actions/App/Customer/Controllers/StoreCustomerController';
import { create, index } from '@/routes/customers';
import type { Option } from '@/types';

export default function CreateCustomer({
    statuses,
    billingTypes,
}: {
    statuses: Option[];
    billingTypes: Option[];
}) {
    return (
        <>
            <Head title="Add customer" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Add customer"
                    description="Start with a client you're working with this week."
                />
                <CustomerForm
                    action={StoreCustomerController.form()}
                    statuses={statuses}
                    billingTypes={billingTypes}
                    submitLabel="Add customer"
                    cancelHref={index().url}
                />
            </div>
        </>
    );
}

CreateCustomer.layout = {
    breadcrumbs: [
        { title: 'Customers', href: index() },
        { title: 'Add customer', href: create() },
    ],
};
