import { Head, setLayoutProps } from '@inertiajs/react';
import PageHeader from '@/components/page-header';
import ServiceForm from '@/components/services/service-form';
import StoreServiceController from '@/actions/App/Service/Controllers/StoreServiceController';
import {
    index as customersIndex,
    show as showCustomer,
} from '@/routes/customers';
import type { Customer, CustomerOption, Option } from '@/types';

export default function CreateService({
    customer,
    statuses,
    locations,
}: {
    customer: Customer;
    statuses: Option[];
    locations: CustomerOption[];
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Customers', href: customersIndex() },
            { title: customer.name, href: showCustomer(customer.id) },
            { title: 'Add service', href: showCustomer(customer.id) },
        ],
    });

    return (
        <>
            <Head title="Add service" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Add service"
                    description={`For ${customer.name}`}
                />
                <ServiceForm
                    action={StoreServiceController.form(customer.id)}
                    statuses={statuses}
                    locations={locations}
                    submitLabel="Add service"
                    cancelHref={showCustomer(customer.id).url}
                />
            </div>
        </>
    );
}
