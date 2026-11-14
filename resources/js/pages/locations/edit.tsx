import { Head, setLayoutProps } from '@inertiajs/react';
import ConfirmDelete from '@/components/confirm-delete';
import PageHeader from '@/components/page-header';
import LocationForm from '@/components/locations/location-form';
import DeleteLocationController from '@/actions/App/Location/Controllers/DeleteLocationController';
import UpdateLocationController from '@/actions/App/Location/Controllers/UpdateLocationController';
import {
    index as customersIndex,
    show as showCustomer,
} from '@/routes/customers';
import type { Customer, Location } from '@/types';

export default function EditLocation({
    location,
    customer,
}: {
    location: Location;
    customer: Customer;
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Customers', href: customersIndex() },
            { title: customer.name, href: showCustomer(customer.id) },
            { title: 'Edit location', href: showCustomer(customer.id) },
        ],
    });

    return (
        <>
            <Head title="Edit location" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Edit location"
                    description={`For ${customer.name}`}
                    actions={
                        <ConfirmDelete
                            action={DeleteLocationController.form(location.id)}
                            title="Delete this location?"
                            description="This can't be undone."
                        />
                    }
                />
                <LocationForm
                    action={UpdateLocationController.form(location.id)}
                    location={location}
                    submitLabel="Save"
                    cancelHref={showCustomer(customer.id).url}
                />
            </div>
        </>
    );
}
