import { Head, setLayoutProps } from '@inertiajs/react';
import ConfirmDelete from '@/components/confirm-delete';
import PageHeader from '@/components/page-header';
import ServiceForm from '@/components/services/service-form';
import DeleteServiceController from '@/actions/App/Service/Controllers/DeleteServiceController';
import UpdateServiceController from '@/actions/App/Service/Controllers/UpdateServiceController';
import {
    index as customersIndex,
    show as showCustomer,
} from '@/routes/customers';
import type {
    Abilities,
    Customer,
    CustomerOption,
    Option,
    Service,
} from '@/types';

export default function EditService({
    can,
    service,
    customer,
    statuses,
    locations,
}: {
    can: Abilities;
    service: Service;
    customer: Customer;
    statuses: Option[];
    locations: CustomerOption[];
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Customers', href: customersIndex() },
            { title: customer.name, href: showCustomer(customer.id) },
            { title: 'Edit service', href: showCustomer(customer.id) },
        ],
    });

    return (
        <>
            <Head title="Edit service" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Edit service"
                    description={`For ${customer.name}`}
                    actions={
                        can.delete && (
                            <ConfirmDelete
                                action={DeleteServiceController.form(
                                    service.id,
                                )}
                                title="Delete this service?"
                                description="This can't be undone."
                            />
                        )
                    }
                />
                <ServiceForm
                    action={UpdateServiceController.form(service.id)}
                    service={service}
                    statuses={statuses}
                    locations={locations}
                    submitLabel="Save"
                    cancelHref={showCustomer(customer.id).url}
                />
            </div>
        </>
    );
}
