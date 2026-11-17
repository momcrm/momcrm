import { Head, setLayoutProps } from '@inertiajs/react';
import LinkedRecords from '@/components/custom-fields/linked-records';
import ConfirmDelete from '@/components/confirm-delete';
import PageHeader from '@/components/page-header';
import LocationForm from '@/components/locations/location-form';
import DeleteLocationController from '@/actions/App/Location/Controllers/DeleteLocationController';
import UpdateLocationController from '@/actions/App/Location/Controllers/UpdateLocationController';
import {
    index as customersIndex,
    show as showCustomer,
} from '@/routes/customers';
import type { Abilities, Customer, LinkedRecordGroup, Location } from '@/types';

export default function EditLocation({
    can,
    linkedRecords,
    location,
    customer,
}: {
    can: Abilities;
    /** What points at this record through record-link custom fields. */
    linkedRecords: LinkedRecordGroup[];
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
                        can.delete && (
                            <ConfirmDelete
                                action={DeleteLocationController.form(
                                    location.id,
                                )}
                                title="Delete this location?"
                                description="This can't be undone."
                            />
                        )
                    }
                />
                <LocationForm
                    action={UpdateLocationController.form(location.id)}
                    location={location}
                    submitLabel="Save"
                    cancelHref={showCustomer(customer.id).url}
                />
                <div className="max-w-2xl">
                    <LinkedRecords groups={linkedRecords} />
                </div>
            </div>
        </>
    );
}
