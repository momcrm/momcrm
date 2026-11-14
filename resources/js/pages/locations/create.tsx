import { Head, setLayoutProps } from '@inertiajs/react';
import PageHeader from '@/components/page-header';
import LocationForm from '@/components/locations/location-form';
import StoreLocationController from '@/actions/App/Location/Controllers/StoreLocationController';
import {
    index as customersIndex,
    show as showCustomer,
} from '@/routes/customers';
import type { Customer } from '@/types';

export default function CreateLocation({ customer }: { customer: Customer }) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Customers', href: customersIndex() },
            { title: customer.name, href: showCustomer(customer.id) },
            { title: 'Add location', href: showCustomer(customer.id) },
        ],
    });

    return (
        <>
            <Head title="Add location" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Add location"
                    description={`For ${customer.name}`}
                />
                <LocationForm
                    action={StoreLocationController.form(customer.id)}
                    submitLabel="Add location"
                    cancelHref={showCustomer(customer.id).url}
                />
            </div>
        </>
    );
}
