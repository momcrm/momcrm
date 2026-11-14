import { Head, setLayoutProps } from '@inertiajs/react';
import PageHeader from '@/components/page-header';
import AssetForm from '@/components/assets/asset-form';
import StoreAssetController from '@/actions/App/Asset/Controllers/StoreAssetController';
import {
    index as customersIndex,
    show as showCustomer,
} from '@/routes/customers';
import type { Customer, CustomerOption, Option } from '@/types';

export default function CreateAsset({
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
            { title: 'Add asset', href: showCustomer(customer.id) },
        ],
    });

    return (
        <>
            <Head title="Add asset" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Add asset"
                    description={`For ${customer.name}`}
                />
                <AssetForm
                    action={StoreAssetController.form(customer.id)}
                    statuses={statuses}
                    locations={locations}
                    submitLabel="Add asset"
                    cancelHref={showCustomer(customer.id).url}
                />
            </div>
        </>
    );
}
